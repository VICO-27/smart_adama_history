<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\PdfToText\Pdf;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\Section;

class ParseSmartAdamaBook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'book:parse';

    /**
     * The console command description.
     */
    protected $description = 'Parses the SA-Book.pdf file using Spatie and poppler-utils';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Native Ubuntu PDF Parsing Pipeline...');

        $pdfPath = base_path('SA-Book.pdf');
        
        if (!file_exists($pdfPath)) {
            $this->error("Could not find the PDF file at: {$pdfPath}");
            return;
        }

        $this->info('Extracting text using native pdftotext binary...');
        
        // Use shell_exec to call the Ubuntu binary directly and capture errors (2>&1)
        // The '-' at the end tells pdftotext to output the text directly to our script
        $command = 'pdftotext -layout ' . escapeshellarg($pdfPath) . ' - 2>/dev/null';        $text = shell_exec($command);

        if (!$text || trim($text) === '') {
            $this->error('Extraction failed. The PDF might be a scanned image, or pdftotext failed silently.');
            $this->line('Command output: ' . $text);
            return;
        }

        $this->info('Extraction complete! Raw text length: ' . strlen($text) . ' characters.');

        // 1. Setup the Book
        $book = Book::firstOrCreate(
            ['title' => 'Smart Adama: Complete Guide'],
            ['status' => 'published']
        );

        $this->info('Clearing old placeholder chapters...');
        $book->chapters()->delete();

        $this->info('Slicing text into chapters...');

        // 2. The Regex Splitter
        $parts = preg_split('/(Chapter\s+\d+[:\s]+[^\n]*)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $chapterOrder = 1;

        if (trim($parts[0]) !== '') {
            $this->saveChapter($book, 'Introduction & Preface', $parts[0], $chapterOrder++);
        }

        for ($i = 1; $i < count($parts); $i += 2) {
            $chapterTitle = trim($parts[$i]);
            $chapterText = isset($parts[$i+1]) ? trim($parts[$i+1]) : '';
            
            $cleanTitle = substr($chapterTitle, 0, 150);
            $this->saveChapter($book, $cleanTitle, $chapterText, $chapterOrder++);
        }

        $this->info('✅ Book successfully parsed and saved to the database!');
    }

    /**
     * Helper to save a chapter and its section.
     */
    private function saveChapter(Book $book, string $title, string $text, int $order)
    {
        $this->line("Saving: {$title}...");

        $chapter = $book->chapters()->create([
            'title' => $title,
            'order' => $order,
            'ingestion_status' => 'draft', 
        ]);

        // Clean terminal warnings if any slipped through
        $cleanText = preg_replace('/Syntax Error.*?(?=\n|$)/i', '', $text);

        // Split text into paragraphs (sections) by double newlines or line breaks
        // This prevents the "Complete Chapter Text" wall of text in your reader UI
        $paragraphs = array_filter(explode("\n\n", $cleanText));
        
        $sectionOrder = 1;
        $chunkLimit = 1500; // Max characters per section for readable pages

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) continue;

            // If a paragraph is too long, chunk it further
            $chunks = str_split($para, $chunkLimit);
            foreach ($chunks as $chunk) {
                $chapter->sections()->create([
                    'title' => 'Section ' . $sectionOrder,
                    'order' => $sectionOrder,
                    'raw_text' => trim($chunk)
                ]);
                $sectionOrder++;
            }
        }

        // Fallback if no paragraphs matched
        if ($chapter->sections()->count() === 0) {
            $chapter->sections()->create([
                'title' => 'Overview',
                'order' => 1,
                'raw_text' => 'No text content available.'
            ]);
        }
    }
}