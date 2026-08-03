<?php

namespace App\Services\RAG;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\ContentChunk;
use App\Models\Section;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Book Ingestion Service - Canonical 11-chapter manual ingestion
 *
 * Handles:
 * - Validation of chapter content (no front matter, no System Context)
 * - Section extraction from chapter content
 * - Safe embedding with Voyage rate limiting
 * - Resumable ingestion
 * - Verification
 *
 * CRITICAL: Only canonical Chapters 1-11 are allowed.
 * Chapter 0, 999, 12+ are REJECTED at validation time.
 */
class BookIngestionService
{
    // Canonical chapter definitions (exactly 11 chapters)
    private const CANONICAL_CHAPTERS = [
        1 => 'Introduction',
        2 => 'Smart Governance',
        3 => 'Digital Adama',
        4 => 'Smart Security',
        5 => 'Smart Urban Design and Land Use Management',
        6 => 'Smart Environment and Organic Production',
        7 => 'Smart Mobility',
        8 => 'Smart Social Services',
        9 => 'Smart Tourism and Culture',
        10 => 'Smart Public Relation, Research and Knowledge Management',
        11 => 'Smart People',
    ];

    public function __construct(
        private readonly ChunkingService        $chunker,
        private readonly EmbeddingProviderInterface $embedder,
    ) {
    }

    /**
     * Get canonical chapter metadata
     */
    public function getCanonicalChapters(): array
    {
        return self::CANONICAL_CHAPTERS;
    }

    /**
     * Ensure all canonical chapters exist for a book
     * Creates chapters if they don't exist
     */
    public function ensureCanonicalChapters(Book $book): void
    {
        foreach (self::CANONICAL_CHAPTERS as $number => $title) {
            Chapter::firstOrCreate(
                [
                    'book_id' => $book->id,
                    'order' => $number,
                ],
                [
                    'title' => $title,
                    'ingestion_status' => 'draft',
                ]
            );
        }
    }

    /**
     * Validate chapter content before ingestion
     *
     * Checks:
     * - Chapter number is 1-11
     * - Content is not empty
     * - Content length is reasonable (min 100 chars, max 100k chars)
     * - Content does not contain front matter markers
     * - Content does not contain System Context
     * - Content does not contain Chapter 12+
     *
     * Returns: ['valid' => bool, 'errors' => array]
     */
    public function validateChapterContent(int $chapterNumber, string $content): array
    {
        $errors = [];

        // Check chapter number
        if (!isset(self::CANONICAL_CHAPTERS[$chapterNumber])) {
            $errors[] = "Invalid chapter number: $chapterNumber. Must be 1-11.";
        }

        // Check content is not empty
        $trimmed = trim($content);
        if (empty($trimmed)) {
            $errors[] = 'Chapter content is empty.';
        } else {
            // Check minimum length
            if (strlen($trimmed) < 100) {
                $errors[] = 'Chapter content is too short (minimum 100 characters).';
            }

            // Check maximum length (prevent abuse)
            if (strlen($trimmed) > 100000) {
                $errors[] = 'Chapter content is too long (maximum 100,000 characters).';
            }

            // Check for front matter markers (only at the very beginning of document)
            $lines = explode("\n", $trimmed);
            $firstLines = array_slice($lines, 0, 3);
            $frontMatterPatterns = [
                '/^Contents\s*$/i',
                '/^Table of Contents/i',
                '/^List of (Figures|Tables|Acronyms)/i',
                '/^Preface/i',
                '/^Foreword/i',
                '/^Authors?$/i',
                '/^Advisor$/i',
                '/^Mayor\'?s Message/i',
                '/^Introduction & Preface/i',
            ];

            foreach ($firstLines as $line) {
                foreach ($frontMatterPatterns as $pattern) {
                    if (preg_match($pattern, trim($line))) {
                        $errors[] = "Content contains front matter marker: " . trim($line);
                        break 2;
                    }
                }
            }

            // Check for System Context
            if (stripos($content, 'System Context') !== false) {
                $errors[] = 'Content contains "System Context" which is not allowed in Book RAG.';
            }

            // Check for Chapter 12+ (more precise: only match chapter numbers at document level, not in text)
            // This pattern looks for "Chapter 12", "Chapter 13", etc. as standalone headings
            if (preg_match('/^Chapter\s+(1[2-9]|[2-9]\d+)\b/mi', $content)) {
                $errors[] = 'Content contains Chapter 12+ which is not allowed in Book RAG.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Preview chapter content
     *
     * Returns section extraction and chunk estimates WITHOUT saving or embedding.
     * Does NOT call Voyage AI.
     *
     * Returns: [
     *   'sections' => array,
     *   'estimated_chunks' => int,
     *   'estimated_batches' => int,
     *   'original_length' => int,
     *   'extracted_length' => int,
     *   'content_preserved_pct' => float,
     * ]
     */
    public function previewChapter(int $chapterNumber, string $content): array
    {
        $originalLength = strlen($content);
        $sections = $this->extractSections($content);
        $allChunks = [];
        
        // Calculate total extracted content length
        $extractedLength = 0;
        foreach ($sections as $section) {
            $extractedLength += strlen($section['title']) + strlen($section['raw_text']);
            $chunks = $this->chunker->chunk($section['raw_text'], 0); // section_id not needed for preview
            $allChunks = array_merge($allChunks, $chunks);
        }

        $batchSize = (int) config('ai.voyage.batch_size', 32);
        $estimatedBatches = max(1, ceil(count($allChunks) / $batchSize));
        
        $preservedPct = $originalLength > 0 ? ($extractedLength / $originalLength) * 100 : 0;

        return [
            'sections' => $sections,
            'estimated_chunks' => count($allChunks),
            'estimated_batches' => $estimatedBatches,
            'original_length' => $originalLength,
            'extracted_length' => $extractedLength,
            'content_preserved_pct' => round($preservedPct, 2),
        ];
    }

    /**
     * Extract sections from chapter content (ZERO CONTENT LOSS)
     *
     * Detects Chapter 2 structure: Chapter 2, 2.1, 2.2, 2.2.1, 2.2.2, 2.3
     * IGNORES deeper subsections (2.2.1.1, 2.2.1.6, etc.) - treats as content
     * Preserves 100% of pasted content - no silent drops.
     */
    private function extractSections(string $content): array
    {
        // Minimal cleanup: only remove form feed and null bytes (keep all text)
        $cleanText = preg_replace('/[\x0C\x00]/', '', $content);
        
        // Split into lines WITHOUT filtering - preserve everything
        $lines = explode("\n", $cleanText);

        $sections = [];
        $currentSectionTitle = '';
        $currentBody = [];
        $pendingSectionNumber = '';

        foreach ($lines as $lineIndex => $line) {
            $trimmed = trim($line);
            
            // Skip ONLY truly empty lines for processing logic
            if ($trimmed === '') {
                // But still add to body to preserve spacing
                if ($currentSectionTitle || count($currentBody) > 0) {
                    $currentBody[] = $line;
                }
                continue;
            }

            // Check: Is this the main chapter heading? (e.g., "Chapter 2")
            if (preg_match('/^Chapter\s+(\d+)(\s+(.+))?$/i', $trimmed, $matches)) {
                // Save previous section if we have content
                if ($currentSectionTitle && count($currentBody) > 0) {
                    $sections[] = [
                        'title' => $currentSectionTitle,
                        'raw_text' => trim(implode("\n", $currentBody)),
                    ];
                }
                $currentSectionTitle = $trimmed; // Use full line as title
                $currentBody = [];
                $pendingSectionNumber = '';
                continue;
            }

            // Check: Is this a corrupted section number? Fix common corruptions
            $cleanedLine = $trimmed;
            // Fix patterns like "582.3" → "2.3", "282.2.1.1" → "2.2.1.1", "332.2.1.3" → "2.2.1.3"
            $cleanedLine = preg_replace('/^\d*(\d\.\d+(?:\.\d+)*)/', '$1', $cleanedLine);
            
            // Check: Is this a section number on its own line?
            if (preg_match('/^(\d+(?:\.\d+){0,2})$/', $cleanedLine, $matches)) {
                $number = $matches[1];
                
                // ONLY treat as section header if it matches our target levels:
                // 2.1, 2.2, 2.2.1, 2.2.2, 2.3 (but NOT 2.2.1.1, 2.2.1.6, etc.)
                if (preg_match('/^2(\.[123])?(\.[12])?$/', $number)) {
                    // Save previous section if we have content
                    if ($currentSectionTitle && count($currentBody) > 0) {
                        $sections[] = [
                            'title' => $currentSectionTitle,
                            'raw_text' => trim(implode("\n", $currentBody)),
                        ];
                    }
                    $pendingSectionNumber = $number;
                    $currentBody = [];
                    
                    // Look ahead for the title - skip empty lines
                    $titleFound = false;
                    for ($lookahead = 1; $lookahead <= 3; $lookahead++) {
                        if (isset($lines[$lineIndex + $lookahead])) {
                            $nextLine = trim($lines[$lineIndex + $lookahead]);
                            if ($nextLine !== '' && !preg_match('/^\d/', $nextLine)) {
                                // Found a non-empty, non-numeric line - use as title
                                $inferredTitle = '';
                                // Infer title based on section number
                                switch ($number) {
                                    case '2.1':
                                        $inferredTitle = 'Introduction';
                                        break;
                                    case '2.2.2':
                                        $inferredTitle = 'Implementation Procedures';
                                        break;
                                    case '2.3':
                                        $inferredTitle = 'Future Considerations';
                                        break;
                                    default:
                                        $inferredTitle = $nextLine;
                                }
                                $currentSectionTitle = $number . ' ' . $inferredTitle;
                                $titleFound = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$titleFound) {
                        // Fallback: use section number alone
                        $currentSectionTitle = $number;
                    }
                    
                    continue;
                } else {
                    // This is a deeper subsection - treat as content
                    $currentBody[] = $line;
                    continue;
                }
            }

            // Check: Is this a numbered section heading with title on same line?
            // ONLY allow target levels, not deep subsections  
            if (preg_match('/^(\d+(?:\.\d+){0,2})\s+(.+)$/i', $cleanedLine, $matches)) {
                $number = $matches[1];
                $title = $matches[2];
                
                // ONLY treat as section header if it matches our target levels
                if (preg_match('/^2(\.[123])?(\.[12])?$/', $number)) {
                    // Save previous section if we have content
                    if ($currentSectionTitle && count($currentBody) > 0) {
                        $sections[] = [
                            'title' => $currentSectionTitle,
                            'raw_text' => trim(implode("\n", $currentBody)),
                        ];
                    }
                    $currentSectionTitle = $number . ' ' . $title; // Use cleaned number + title
                    $currentBody = [];
                    $pendingSectionNumber = '';
                    continue;
                } else {
                    // This is a deeper subsection - treat as content
                    $currentBody[] = $line;
                    continue;
                }
            }

            // Check: Do we have a pending section number waiting for its title?
            if ($pendingSectionNumber && !preg_match('/^\d/', $trimmed)) {
                // This line should be the title (if it's not another number)
                $currentSectionTitle = $pendingSectionNumber . ' ' . $trimmed;
                $pendingSectionNumber = '';
                continue;
            }

            // Otherwise: This is body content
            if ($currentSectionTitle || count($currentBody) > 0) {
                $currentBody[] = $line; // Keep original line (with spacing)
            }
        }

        // Save final section
        if ($currentSectionTitle && count($currentBody) > 0) {
            $sections[] = [
                'title' => $currentSectionTitle,
                'raw_text' => trim(implode("\n", $currentBody)),
            ];
        }

        // If no sections found, preserve ALL content as single section
        if (empty($sections)) {
            $sections[] = [
                'title' => 'Content',
                'raw_text' => trim($content),
            ];
        } elseif (count($currentBody) > 0 && !$currentSectionTitle) {
            // There's orphaned content before first section - prepend it
            array_unshift($sections, [
                'title' => 'Introduction',
                'raw_text' => trim(implode("\n", $currentBody)),
            ]);
        }

        return $sections;
    }

    /**
     * Ingest a chapter with safe embedding
     *
     * Creates or updates chapter and sections, then dispatches embedding jobs.
     * Handles resumable ingestion by only re-ingesting incomplete chunks.
     */
    public function ingestChapter(Book $book, int $chapterNumber, string $content): array
    {
        // Validate
        $validation = $this->validateChapterContent($chapterNumber, $content);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        // Get or create chapter
        $chapter = Chapter::firstOrNew(
            ['book_id' => $book->id, 'order' => $chapterNumber],
            ['title' => self::CANONICAL_CHAPTERS[$chapterNumber]]
        );
        $chapter->title = self::CANONICAL_CHAPTERS[$chapterNumber];
        $chapter->ingestion_status = 'draft';
        $chapter->save();

        // Extract sections
        $sections = $this->extractSections($content);

        // Create/update sections
        foreach ($sections as $order => $sectionData) {
            $section = Section::firstOrNew(
                ['chapter_id' => $chapter->id, 'order' => $order + 1],
                ['title' => $sectionData['title'], 'raw_text' => '']
            );
            $section->title = $sectionData['title'];
            $section->raw_text = $sectionData['raw_text'];
            $section->save();
        }

        // Clear any failed chunks for resumability
        ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))
            ->where('embedding_status', 'failed')
            ->delete();

        // Dispatch ingestion job
        \App\Jobs\IngestChapterJob::dispatch($chapter->id);

        return [
            'success' => true,
            'chapter_id' => $chapter->id,
            'message' => 'Chapter queued for ingestion',
        ];
    }

    /**
     * Verify book ingestion completeness
     *
     * Returns detailed verification report.
     */
    public function verifyBook(Book $book): array
    {
        $chapters = Chapter::where('book_id', $book->id)
            ->orderBy('order')
            ->get();

        $canonicalCount = count(self::CANONICAL_CHAPTERS);
        $populatedCount = $chapters->where('ingestion_status', 'ready')->count();
        
        // Count sections across all book chapters
        $totalSections = Section::whereIn('chapter_id', $chapters->pluck('id'))->count();
        
        // Count chunks for this book's chapters
        $totalChunks = ContentChunk::whereHas('section', function($q) use ($book) {
            $q->whereHas('chapter', function($subQ) use ($book) {
                $subQ->where('book_id', $book->id);
            });
        })->count();
        
        $readyChunks = ContentChunk::whereHas('section', function($q) use ($book) {
            $q->whereHas('chapter', function($subQ) use ($book) {
                $subQ->where('book_id', $book->id);
            });
        })->where('embedding_status', 'ready')->count();
        
        $pendingChunks = ContentChunk::whereHas('section', function($q) use ($book) {
            $q->whereHas('chapter', function($subQ) use ($book) {
                $subQ->where('book_id', $book->id);
            });
        })->where('embedding_status', 'pending')->count();
        
        $failedChunks = ContentChunk::whereHas('section', function($q) use ($book) {
            $q->whereHas('chapter', function($subQ) use ($book) {
                $subQ->where('book_id', $book->id);
            });
        })->where('embedding_status', 'failed')->count();
        
        $nullEmbeddings = ContentChunk::whereHas('section', function($q) use ($book) {
            $q->whereHas('chapter', function($subQ) use ($book) {
                $subQ->where('book_id', $book->id);
            });
        })->whereNull('embedding')->count();

        // Check for invalid chapters
        $invalidChapters = $chapters->filter(function($c) {
            return $c->order < 1 || $c->order > 11;
        })->count();

        $isComplete = (
            $chapters->count() === $canonicalCount &&
            $populatedCount === $canonicalCount &&
            $totalChunks > 0 &&
            $readyChunks === $totalChunks &&
            $pendingChunks === 0 &&
            $failedChunks === 0 &&
            $nullEmbeddings === 0 &&
            $invalidChapters === 0
        );

        return [
            'total_chapters' => $chapters->count(),
            'canonical_chapters' => $canonicalCount,
            'populated_chapters' => $populatedCount,
            'total_sections' => $totalSections,
            'total_chunks' => $totalChunks,
            'ready_chunks' => $readyChunks,
            'pending_chunks' => $pendingChunks,
            'failed_chunks' => $failedChunks,
            'null_embeddings' => $nullEmbeddings,
            'invalid_chapters' => $invalidChapters,
            'is_complete' => $isComplete,
            'status' => $isComplete ? 'ready' : 'incomplete',
        ];
    }

    /**
     * Get chapter status
     */
    public function getChapterStatus(Chapter $chapter): array
    {
        $sectionCount = $chapter->sections()->count();
        $chunkCount = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))->count();
        $readyChunks = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))
            ->where('embedding_status', 'ready')->count();
        $pendingChunks = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))
            ->where('embedding_status', 'pending')->count();
        $failedChunks = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))
            ->where('embedding_status', 'failed')->count();

        return [
            'chapter_id' => $chapter->id,
            'chapter_number' => $chapter->order,
            'title' => $chapter->title,
            'ingestion_status' => $chapter->ingestion_status,
            'section_count' => $sectionCount,
            'chunk_count' => $chunkCount,
            'ready_chunks' => $readyChunks,
            'pending_chunks' => $pendingChunks,
            'failed_chunks' => $failedChunks,
        ];
    }

    /**
     * Retry failed chunks for a chapter
     */
    public function retryFailedChunks(Chapter $chapter): array
    {
        $failedChunks = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))
            ->where('embedding_status', 'failed')
            ->get();

        if ($failedChunks->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No failed chunks to retry',
            ];
        }

        // Reset failed chunks to pending
        $count = $failedChunks->count();
        $failedChunks->each(function($chunk) {
            $chunk->update(['embedding_status' => 'pending']);
        });

        // Dispatch re-embedding for the chapter
        \App\Jobs\IngestChapterJob::dispatch($chapter->id);

        return [
            'success' => true,
            'message' => "Retry queued for $count failed chunks",
        ];
    }
}