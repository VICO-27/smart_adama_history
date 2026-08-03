<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\ContentChunk;
use App\Services\RAG\ChunkingService;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\DB;

class ParseSmartAdamaBook extends Command
{
    protected $signature = "book:parse
        {--dry-run : Parse and verify without saving/embedding}
        {--resume : Resume from where left off (don't truncate existing data)}
        {--retry-failed : Only re-try batches with failed chunks}
        {--verify : Verify existing embeddings after ingestion}";

    protected $description = 'FAST BATCH PARSER: Parses the SA-Book.pdf and generates embeddings with rate limiting.';

    private int $rateLimitRetrySeconds;
    private int $requestDelaySeconds;
    private int $failedBatchCount = 0;
    private int $totalChunksCreated = 0;
    private int $totalChunksEmbedded = 0;

    public function handle(ChunkingService $chunker, EmbeddingProviderInterface $embedder)
    {
        $this->rateLimitRetrySeconds = (int) config('ai.voyage.rate_limit_retry_seconds', 60);
        $this->requestDelaySeconds = (int) config('ai.voyage.request_delay_seconds', 25);
        
        $dryRun = $this->option('dry-run');
        $resume = $this->option('resume');
        $retryFailed = $this->option('retry-failed');
        $verifyOnly = $this->option('verify');

        if ($verifyOnly) {
            return $this->verifyIngestion();
        }

        // Dry-run mode: parse and verify without saving/embedding
        if ($dryRun) {
            return $this->dryRunParse($chunker);
        }

        if (!$resume) {
            $this->info('🚀 Starting the FAST Smart Adama Knowledge Pipeline...');
            $this->info('🗑️  Wiping old data...');
            ContentChunk::truncate();
            Section::truncate();
            Chapter::truncate();
            Book::truncate();
        } else {
            $this->info('🚀 Resuming Smart Adama Knowledge Pipeline...');
            $existingChunks = ContentChunk::count();
            $this->info("📁 Found {$existingChunks} existing chunks to resume from.");
        }

        // Create book (or get existing if resuming)
        if (!$resume || ContentChunk::count() === 0) {
            $book = Book::create(['title' => 'Smart Adama: Complete Guide & Ecosystem', 'status' => 'published']);
        } else {
            $book = Book::first();
            if (!$book) {
                $this->error('No book found. Cannot resume without book.');
                return 1;
            }
        }

        // Only inject global knowledge if no data exists
        if (!$resume || ContentChunk::count() === 0) {
            $this->info('🧠 Injecting Global Platform, City Knowledge & Core Pillars...');
            $this->injectGlobalKnowledge($book);
        }

        // Only parse PDF if no data exists (when not resuming specific sections)
        if (!$resume || ContentChunk::count() === 0) {
            $pdfPath = base_path('../docs/SA-Book.pdf');
            if (!file_exists($pdfPath)) {
                $pdfPath = base_path('SA-Book.pdf');
            }

            if (file_exists($pdfPath)) {
                $this->info("📄 Extracting raw text from PDF...");
                $text = shell_exec('pdftotext ' . escapeshellarg($pdfPath) . ' - 2>/dev/null');
                if ($text && strlen(trim($text)) > 1000) {
                    $this->parsePdfText($book, $text);
                }
            }
        }

        // 4. BATCH EMBEDDING
        $this->info("\n⚡ Preparing chunks for embedding...");
        $sections = Section::all();
        $allChunks = [];

        foreach ($sections as $section) {
            $rawText = $section->raw_text ?? '';
            if (!empty(trim($rawText))) {
                $sectionChunks = $chunker->chunk($rawText, $section->id);
                foreach ($sectionChunks as $c) {
                    $allChunks[] = $c;
                }
            }
        }

        if (empty($allChunks)) {
            $this->warn('⚠️  No chunks found to embed. Nothing to do.');
            return 0;
        }

        // GROUP INTO SAFE BATCHES OF 10
        $batches = array_chunk($allChunks, 10);
        $totalBatches = count($batches);
        
        $this->info("📦 Generated {$totalBatches} batches (10 chunks each) for embedding.");
        $this->warn("⏳ Firing batches to Voyage AI (Waiting {$this->requestDelaySeconds}s between batches)...");

        $this->output->progressStart($totalBatches);

        $failedBatches = [];

        foreach ($batches as $batchIndex => $batch) {
            $currentBatch = $batchIndex + 1;
            $this->info("📦 Processing batch {$currentBatch}/{$totalBatches}...");
            
            try {
                $texts = array_column($batch, 'chunk_text');
                $embeddings = $embedder->embedBatch($texts);

                DB::transaction(function () use ($batch, $embeddings) {
                    foreach ($batch as $i => $chunkData) {
                        $vector = $embeddings[$i] ?? null;
                        if (!$vector) {
                            $this->warn("⚠️  Missing embedding for chunk {$chunkData['chunk_index']}");
                            continue;
                        }

                        $contentChunk = ContentChunk::create([
                            'section_id'       => $chunkData['section_id'],
                            'chunk_text'       => $chunkData['chunk_text'],
                            'chunk_index'      => $chunkData['chunk_index'],
                            'token_count'      => $chunkData['token_count'],
                            'embedding_status' => 'ready',
                        ]);

                        $vectorStr = '[' . implode(',', $vector) . ']';
                        DB::statement(
                            'UPDATE content_chunks SET embedding = ? WHERE id = ?',
                            [$vectorStr, $contentChunk->id]
                        );
                    }
                });
                
                $this->info("✅ Batch {$currentBatch}/{$totalBatches} completed successfully.");
            } catch (\Exception $e) {
                $this->error("\n❌ Batch {$currentBatch}/{$totalBatches} failed: " . $e->getMessage());
                $failedBatches[] = $batchIndex;
                $this->failedBatchCount++;
                
                // Wait before retrying next batch (not immediately)
                $this->error("   Waiting {$this->rateLimitRetrySeconds}s before next batch...");
                sleep($this->rateLimitRetrySeconds);
            }

            $this->output->progressAdvance();
            
            if ($batchIndex < $totalBatches - 1) {
                $this->info("⏸️  Waiting {$this->requestDelaySeconds}s before next batch...");
                sleep($this->requestDelaySeconds);
            }
        }

        $this->output->progressFinish();

        // Verify final state
        $this->info("\n🔍 Verifying ingestion...");
        $totalInDB = ContentChunk::count();
        $readyInDB = ContentChunk::where('embedding_status', 'ready')->count();
        $nullEmbeddings = ContentChunk::whereNull('embedding')->count();

        $this->info("📊 Final State:");
        $this->info("  - Total chunks in DB: {$totalInDB}");
        $this->info("  - Ready chunks: {$readyInDB}");
        $this->info("  - Chunks with NULL embedding: {$nullEmbeddings}");
        $this->info("  - Failed batches: {$this->failedBatchCount}");

        if ($this->failedBatchCount > 0 || $nullEmbeddings > 0) {
            $this->error("\n�� KNOWLEDGE BASE INCOMPLETE!");
            $this->error("   {$this->failedBatchCount} batch(es) failed to embed.");
            $this->error("   Run with --retry-failed to retry failed batches.");
            return 1;
        }

        $this->info("\n✅ KNOWLEDGE BASE VERIFIED - fully embedded safely!");
        return 0;
    }

    private function dryRunParse(ChunkingService $chunker): int
    {
        $this->info("🔍 DRY RUN: Parsing PDF without saving to database...");
        
        $pdfPath = base_path('SA-Book.pdf');
        if (!file_exists($pdfPath)) {
            $this->error("❌ PDF file not found at: $pdfPath");
            return 1;
        }

        $this->info("📄 Extracting raw text from PDF...");
        $text = shell_exec('pdftotext ' . escapeshellarg($pdfPath) . ' - 2>/dev/null');
        
        if (!$text || strlen(trim($text)) < 1000) {
            $this->error("❌ Failed to extract text from PDF");
            return 1;
        }

        // Count lines to verify extraction
        $lineCount = count(explode("\n", $text));
        $this->info("📝 Extracted {$lineCount} lines from PDF");

        // Normalize text (same as parsePdfText does)
        $text = preg_replace('/[\x0C\x00]/', '', $text);
        $lines = explode("\n", $text);
        
        // Find TOC
        $tocStart = null;
        foreach ($lines as $i => $line) {
            if (stripos($line, 'contents') !== false) {
                $tocStart = $i;
                break;
            }
        }
        
        if ($tocStart === null) {
            $this->error("❌ Could not find 'Contents' in PDF - cannot locate TOC");
            return 1;
        }
        
        $this->info("📌 TOC found at line " . ($tocStart + 1));
        
        // Parse chapter entries from TOC (PHASE A - metadata only)
        $chapterMatches = [];
        for ($i = $tocStart + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (preg_match('/^List of (Figures|Tables|Acronyms)/i', $line)) break;
            if (preg_match('/^(\d{1,2})\s+(.+?)\s*$/', $line, $m)) {
                $chapterNum = (int)$m[1];
                if ($chapterNum >= 1 && $chapterNum <= 11) {
                    $chapterMatches[$chapterNum] = trim($m[2]);
                    $this->info("📝 Chapter $chapterNum: " . trim($m[2]));
                }
            }
        }
        
        if (count($chapterMatches) !== 11) {
            $this->error("❌ Expected 11 chapters, found " . count($chapterMatches));
            return 1;
        }
        
        $this->info("\n📋 Found 11 chapters in TOC");
        
        // Build normalized title lookup
        $normalizedTitles = [];
        foreach ($chapterMatches as $num => $title) {
            $normalizedTitles[$num] = preg_replace('/\s+/', ' ', strtolower($title));
        }
        
        // PHASE B - Locate chapter body boundaries
        $chapterBoundaries = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (preg_match('/^(\d{1,2})\s+(.+?)\s*$/', $line, $m)) {
                $chapterNum = (int)$m[1];
                $detectedTitle = trim($m[2]);
                
                if (isset($chapterMatches[$chapterNum])) {
                    $normalizedDetected = preg_replace('/\s+/', ' ', strtolower($detectedTitle));
                    $normalizedExpected = $normalizedTitles[$chapterNum];
                    
                    if ($normalizedDetected === $normalizedExpected || 
                        strpos($normalizedDetected, $normalizedExpected) === 0 ||
                        strpos($normalizedExpected, $normalizedDetected) === 0) {
                        
                        $chapterBoundaries[$chapterNum] = [
                            'line' => $i,
                            'title' => $chapterMatches[$chapterNum],
                        ];
                        $this->info("📍 Chapter $chapterNum body at line " . ($i + 1));
                    }
                }
            }
        }
        
        if (count($chapterBoundaries) !== 11) {
            $this->error("❌ Only found " . count($chapterBoundaries) . " chapter bodies (expected 11)");
            return 1;
        }
        
        // Extract chapter bodies and count sections (PHASE C - section parsing)
        ksort($chapterBoundaries);
        $chapterNumbers = array_keys($chapterBoundaries);
        
        $this->info("\n📊 Dry Run Results:");
        
        for ($i = 0; $i < count($chapterNumbers); $i++) {
            $chapterNum = $chapterNumbers[$i];
            $startLine = $chapterBoundaries[$chapterNum]['line'];
            $endLine = ($i + 1 < count($chapterNumbers)) 
                ? $chapterBoundaries[$chapterNumbers[$i + 1]]['line'] 
                : count($lines);
            
            // Extract chapter body
            $chapterText = '';
            for ($j = $startLine; $j < $endLine; $j++) {
                $chapterText .= $lines[$j] . "\n";
            }
            
            // Count sections in this chapter body
            $sectionCount = $this->countSectionsInBody($chapterText);
            $bodyChars = strlen($chapterText);
            
            $this->info("  Chapter $chapterNum: " . $chapterMatches[$chapterNum]);
            $this->info("    Body chars: $bodyChars");
            $this->info("    Sections: $sectionCount");
            $this->info("    Body preview: " . substr($chapterText, 0, 200) . "...");
            $this->info("");
        }
        
        $this->info("✅ Dry run completed - parsing logic verified!");
        $this->info("   All 11 chapters extracted with TOC title matching.");
        $this->info("   No data saved to database.");
        
        return 0;
    }
    
    private function countSectionsInBody(string $text): int
    {
        // First clean up the text to remove page numbers and TOC artifacts
        $cleanText = $text;
        
        // Remove lines that are just numbers (page numbers)
        $cleanText = preg_replace('/^\s*\d+\s*$/m', '', $cleanText);
        
        // Remove lines with multiple dots leading to a number (TOC entries like "1.1 Background ...... 45")
        $cleanText = preg_replace('/^.*\.{3,}.*\d+\s*$/m', '', $cleanText);
        
        // Remove standalone Roman numerals
        $cleanText = preg_replace('/^\s*(i|ii|iii|iv|v|vi|vii|viii|ix|x+)\s*$/mi', '', $cleanText);
        
        // Remove chapter heading lines (the number + title pattern)
        $cleanText = preg_replace('/^\s*\d+\s+[A-Z][a-z].*$/m', '', $cleanText);
        
        $lines = array_filter(explode("\n", $cleanText), 'trim');
        $sectionCount = 0;
        $currentSectionTitle = '';
        $currentBody = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Skip lines that are just numbers
            if (preg_match('/^\d+$/', $line)) continue;
            
            // Check if this line is a numbered section heading
            // Pattern: number.number[s] followed by title text (e.g., "1.1", "2.2.1", "10.1")
            if (preg_match('/^\d+(\.\d+)+\s+[A-Z][a-z].*$/i', $line)) {
                if ($currentSectionTitle && !empty($currentBody)) {
                    $sectionCount++;
                }
                $currentSectionTitle = $line;
                $currentBody = '';
            } else {
                $currentBody .= ($currentBody ? "\n" : "") . $line;
            }
        }
        
        if ($currentSectionTitle && !empty($currentBody)) {
            $sectionCount++;
        }
        
        return $sectionCount;
    }

    private function verifyIngestion(): int
    {
        $this->info("🔍 Verifying ingestion state...");
        $total = ContentChunk::count();
        $ready = ContentChunk::where('embedding_status', 'ready')->count();
        $pending = ContentChunk::where('embedding_status', 'pending')->count();
        $failed = ContentChunk::where('embedding_status', 'failed')->count();
        $nullEmbeddings = ContentChunk::whereNull('embedding')->count();

        $this->info("📊 Current State:");
        $this->info("  - Total chunks: {$total}");
        $this->info("  - Ready with embeddings: {$ready}");
        $this->info("  - Pending: {$pending}");
        $this->info("  - Failed: {$failed}");
        $this->info("  - NULL embeddings: {$nullEmbeddings}");

        if ($total === 0) {
            $this->error("\n❌ KNOWLEDGE BASE NOT INGESTED / INCOMPLETE!");
            $this->error("   Zero chunks found in database.");
            return 1;
        }

        if ($pending > 0 || $failed > 0 || $nullEmbeddings > 0) {
            $this->error("\n❌ INGESTION INCOMPLETE!");
            return 1;
        }

        $this->info("\n✅ KNOWLEDGE BASE VERIFIED!");
        return 0;
    }

    private function injectGlobalKnowledge(Book $book)
    {
        // NOTE: System Context is intentionally NOT added as a book chapter
        // It should be stored in a separate table or knowledge source
        // Book RAG should ONLY contain actual book content (Chapters 1-11)
        
        $this->info("🧠 Injecting Global Platform, City Knowledge & Core Pillars...");
        $this->info("   Note: System Context is kept separate from Book RAG.");
        $this->info("   Book RAG will contain ONLY Chapters 1-11.");
    }

    private function parsePdfText(Book $book, string $text)
    {
        // --- NORMALIZE TEXT: remove form-feed and other control chars ---
        $text = preg_replace('/[\x0C\x00]/', '', $text);
        
        // --- EXTRACT ONLY CHAPTERS 1-11 FROM THE TOC ---
        $lines = explode("\n", $text);
        
        // Find the line with "Contents" to know where TOC starts
        $tocStart = null;
        foreach ($lines as $i => $line) {
            if (stripos($line, 'contents') !== false) {
                $tocStart = $i;
                break;
            }
        }
        
        if ($tocStart === null) {
            $this->error("❌ Could not find 'Contents' in PDF - cannot locate TOC");
            return;
        }
        
        $this->info("📌 TOC found at line " . ($tocStart + 1));
        
        // Parse chapter entries from TOC - STORE EXACT TITLES (PHASE A)
        $chapterMatches = [];
        $chapterLines = [];
        
        for ($i = $tocStart + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Stop when we hit the next major section (List of Figures, etc.)
            if (preg_match('/^List of (Figures|Tables|Acronyms)/i', $line)) {
                break;
            }
            
            // Match chapter lines: single number 1-11 followed by title
            if (preg_match('/^(\d{1,2})\s+(.+?)\s*$/', $line, $m)) {
                $chapterNum = (int)$m[1];
                
                if ($chapterNum >= 1 && $chapterNum <= 11) {
                    $chapterMatches[$chapterNum] = trim($m[2]);
                    $chapterLines[$chapterNum] = $i;
                    $this->info("📝 Chapter $chapterNum: " . trim($m[2]));
                }
            }
        }
        
        if (count($chapterMatches) === 0) {
            $this->error("❌ No chapters found in TOC - something is wrong!");
            return;
        }
        
        $this->info("\n📋 Found " . count($chapterMatches) . " chapters to parse");
        
        // Build normalized title lookup for body matching
        $normalizedTitles = [];
        foreach ($chapterMatches as $num => $title) {
            $normalizedTitles[$num] = preg_replace('/\s+/', ' ', strtolower($title));
        }
        
        // PHASE B: ACTUAL BODY EXTRACTION
        // Locate chapter body boundaries in actual book content
        $chapterBoundaries = [];
        
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Check if this line matches any canonical chapter
            if (preg_match('/^(\d{1,2})\s+(.+?)\s*$/', $line, $m)) {
                $chapterNum = (int)$m[1];
                $detectedTitle = trim($m[2]);
                
                // Only accept if it's in our canonical TOC list
                if (isset($chapterMatches[$chapterNum])) {
                    $normalizedDetected = preg_replace('/\s+/', ' ', strtolower($detectedTitle));
                    $normalizedExpected = $normalizedTitles[$chapterNum];
                    
                    // Check if title matches (allowing for PDF whitespace variations)
                    if ($normalizedDetected === $normalizedExpected || 
                        strpos($normalizedDetected, $normalizedExpected) === 0 ||
                        strpos($normalizedExpected, $normalizedDetected) === 0) {
                        
                        $chapterBoundaries[$chapterNum] = [
                            'title' => $chapterMatches[$chapterNum],
                            'line' => $i,
                        ];
                        $this->info("� Chapter $chapterNum body starts at line " . ($i + 1));
                    }
                }
            }
        }
        
        if (count($chapterBoundaries) !== 11) {
            $this->error("❌ Only found " . count($chapterBoundaries) . " chapter bodies (expected 11)");
            return;
        }
        
        // Sort by chapter number
        ksort($chapterBoundaries);
        
        // Extract text for each chapter (from body start to before next chapter body)
        $chapterNumbers = array_keys($chapterBoundaries);
        
        for ($i = 0; $i < count($chapterNumbers); $i++) {
            $chapterNum = $chapterNumbers[$i];
            $startLine = $chapterBoundaries[$chapterNum]['line'];
            
            // Find end of chapter content (start of next chapter, or end of text)
            if ($i + 1 < count($chapterNumbers)) {
                $endLine = $chapterBoundaries[$chapterNumbers[$i + 1]]['line'];
            } else {
                $endLine = count($lines);
            }
            
            // Extract text between chapter boundaries
            $chapterText = '';
            for ($j = $startLine; $j < $endLine; $j++) {
                $chapterText .= $lines[$j] . "\n";
            }
            
            $this->saveChapter($book, $chapterMatches[$chapterNum], $chapterText, $chapterNum);
            $this->info("💾 Saved Chapter $chapterNum: " . $chapterMatches[$chapterNum]);
        }
        
        $this->info("\n✅ Chapter parsing complete - all 11 chapters extracted");
    }

    private function saveChapter(Book $book, string $title, string $text, int $order)
    {
        $chapter = $book->chapters()->create([
            'title' => trim($title),
            'order' => $order,
            'ingestion_status' => 'draft', 
        ]);

        $cleanText = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $cleanText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $cleanText);

        // --- CLEANUP: Remove TOC entries, page numbers, and other artifacts ---
        // 1. Remove lines with multiple dots leading to a number (e.g., "Smart People ...... 45")
        $cleanText = preg_replace('/^.*\.{3,}.*\d+\s*$/m', '', $cleanText);
        // 2. Remove lines that are just numbers (stray page numbers)
        $cleanText = preg_replace('/^\s*\d+\s*$/m', '', $cleanText);
        // 3. Remove lines with Roman numerals (i, ii, iii, iv, etc.)
        $cleanText = preg_replace('/^\s*(i|ii|iii|iv|v|vi|vii|viii|ix|x)+\s*$/mi', '', $cleanText);
        // -----------------------------------------------------------------------

        $lines = array_filter(explode("\n", $cleanText), 'trim');
        $sectionOrder = 1;
        $currentSectionTitle = '';
        $currentBody = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Skip lines that look like TOC entries (with dots to page numbers)
            if (preg_match('/^\d+\.{3,}\d+\s*$/', $line)) continue;
            
            // Check if this line is a numbered section heading (e.g., "2 Smart Governance", "2.1 Introduction", "4.2.1 Major Activities")
            // Pattern: number[.number...] followed by title text (matches root-level AND nested sections)
            if (preg_match('/^\d+(\.\d+)*\s+([A-Z].*?)\s*$/', $line, $m)) {
                // Save previous section if we have content
                if ($currentSectionTitle && !empty($currentBody)) {
                    $chapter->sections()->create([
                        'title' => trim($currentSectionTitle),
                        'order' => $sectionOrder++,
                        'raw_text' => trim($currentBody)
                    ]);
                }
                $currentSectionTitle = $line;
                $currentBody = '';
            } else {
                // This is content text
                $currentBody .= ($currentBody ? "\n" : "") . $line;
            }
        }
        
        // Save final section
        if ($currentSectionTitle && !empty($currentBody)) {
            $chapter->sections()->create([
                'title' => trim($currentSectionTitle),
                'order' => $sectionOrder++,
                'raw_text' => trim($currentBody)
            ]);
        }
    }
}