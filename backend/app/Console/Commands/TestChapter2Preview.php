<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Services\RAG\BookIngestionService;
use Illuminate\Console\Command;

class TestChapter2Preview extends Command
{
    protected $signature = 'test:chapter2-preview';
    protected $description = 'Test Chapter 2 section extraction preview without Voyage API';

    public function handle()
    {
        $this->info('=== SECTION EXTRACTION PREVIEW TEST ===');

        $chapter2 = Chapter::where('order', 2)->first();
        if (!$chapter2 || !$chapter2->content) {
            $this->error('ERROR: Chapter 2 canonical content not found!');
            return 1;
        }

        $canonicalContent = $chapter2->content;
        $sourceLength = strlen($canonicalContent);

        $this->info("Canonical source length: {$sourceLength} chars");
        $this->newLine();

        // First, let's examine the actual structure
        $this->info('=== EXAMINING TEXT STRUCTURE ===');
        $lines = explode("\n", $canonicalContent);
        
        $this->info('Looking for section headers:');
        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            // Look for lines that start with numbers and might be section headers
            if (preg_match('/^(\d+(?:\.\d+)*)\s*(.*)$/', $trimmed, $matches)) {
                $number = $matches[1];
                $title = $matches[2];
                // Show potential section headers (short titles likely to be headers)
                if (strlen($title) > 0 && strlen($title) < 100) {
                    $this->info('Line ' . ($i + 1) . ': "' . $number . '" + "' . $title . '"');
                } elseif (strlen($title) == 0 && strlen($number) <= 10) {
                    $this->info('Line ' . ($i + 1) . ': "' . $number . '" (title on next line?)');
                    // Show next line too
                    if (isset($lines[$i + 1])) {
                        $nextLine = trim($lines[$i + 1]);
                        if (strlen($nextLine) < 100) {
                            $this->info('  Next line: "' . $nextLine . '"');
                        }
                    }
                }
            }
        }
        $this->newLine();

        $service = app(BookIngestionService::class);
        $preview = $service->previewChapter(2, $canonicalContent);

        $this->info('=== EXTRACTION PREVIEW RESULTS ===');
        $this->info("Original length: {$preview['original_length']} chars");
        $this->info("Extracted length: {$preview['extracted_length']} chars");
        $this->info("Difference: " . ($preview['original_length'] - $preview['extracted_length']) . " chars");
        $this->info("Content preserved: {$preview['content_preserved_pct']}%");
        $this->info("Detected sections: " . count($preview['sections']));
        $this->newLine();

        $this->info('=== DETECTED SECTION TITLES ===');
        foreach ($preview['sections'] as $i => $section) {
            $sectionLength = strlen($section['raw_text']);
            $this->info(($i + 1) . ". \"{$section['title']}\" ({$sectionLength} chars)");
        }
        $this->newLine();

        $expected = [
            'Chapter 2 Smart Governance',
            '2.1 Introduction', 
            '2.2 Major Activities and Implementation Strategies',
            '2.2.1 Major Activities',
            '2.2.2 Implementation Procedures',
            '2.3 Future Considerations'
        ];

        $this->info('=== EXPECTED VS ACTUAL ===');
        $this->info('Expected sections (' . count($expected) . '):');
        foreach ($expected as $i => $title) {
            $this->info("  " . ($i + 1) . ". {$title}");
        }
        $this->newLine();

        $this->info('Actual sections (' . count($preview['sections']) . '):');
        foreach ($preview['sections'] as $i => $section) {
            $this->info("  " . ($i + 1) . ". {$section['title']}");
        }
        $this->newLine();

        $this->info('=== VALIDATION ===');
        if ($preview['original_length'] !== $sourceLength) {
            $this->error('ERROR: Source length mismatch!');
        }
        if ($preview['content_preserved_pct'] < 99.0) {
            $this->warn('WARNING: Content preservation below 99%');
        }
        if (count($preview['sections']) !== 5) {
            $this->warn('WARNING: Expected 5 sections, got ' . count($preview['sections']));
        }

        $this->info('Preview test completed. NO Voyage API calls made.');
        return 0;
    }
}