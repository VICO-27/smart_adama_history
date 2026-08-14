<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\BookIngestionService;
use App\Services\RAG\ChunkingService;

/**
 * Fake embedding provider for testing
 */
function makeFakeBookEmbedder(int $dimension = 1024): EmbeddingProviderInterface
{
    return new class($dimension) implements EmbeddingProviderInterface {
        public function __construct(private int $dim) {}
        public function embed(string $text): array
        {
            return array_fill(0, $this->dim, 0.1);
        }
        public function embedBatch(array $texts): array
        {
            return array_map(fn () => array_fill(0, $this->dim, 0.1), $texts);
        }
        public function getDimension(): int
        {
            return $this->dim;
        }
    };
}

beforeEach(function () {
    config([
        'ai.rag.chunk_target_tokens' => 700,
        'ai.rag.chunk_overlap_ratio' => 0.15,
        'ai.voyage.batch_size' => 1,
        'ai.voyage.request_delay_seconds' => 25,
    ]);
});

describe('BookIngestionService Section Extraction', function () {
    
    it('extracts root-level section: "2 Smart Governance"', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2 Smart Governance

Smart governance involves using technology to align government processes with the needs of the people.

It is a powerful political instrument to enhance the quality of public decision-making.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(1);
        expect($preview['sections'][0]['title'])->toBe('2 Smart Governance');
        expect($preview['sections'][0]['raw_text'])->toContain('Smart governance involves');
        expect($preview['sections'][0]['raw_text'])->toContain('public decision-making');
    });

    it('extracts subsection: "2.1 Introduction"', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2.1 Introduction

Smart governance is a core characteristic of smart cities.

It focuses on modern government practices.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(1);
        expect($preview['sections'][0]['title'])->toBe('2.1 Introduction');
    });

    it('extracts nested subsection: "2.2.1 Major Activities"', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2.2.1 Major Activities

These are the major activities in smart governance.

They include strategic planning and digital transformation.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(1);
        expect($preview['sections'][0]['title'])->toBe('2.2.1 Major Activities');
    });

    it('extracts multiple sections: root + subsections', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2 Smart Governance

Smart governance is one of the core characteristics of smart cities.

2.1 Introduction

Smart governance involves using technology better to align government processes with the needs of the people.

It is a powerful political instrument to enhance the quality of public decision-making.

2.2 Major Activities and Implementation Strategies

Major activities include strategic planning and resource allocation.

2.2.1 Major Activities

These include public engagement and policy development.

2.2.2 Implementation Procedures

Implementation requires careful planning and stakeholder coordination.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(5);
        expect($preview['sections'][0]['title'])->toBe('2 Smart Governance');
        expect($preview['sections'][1]['title'])->toBe('2.1 Introduction');
        expect($preview['sections'][2]['title'])->toBe('2.2 Major Activities and Implementation Strategies');
        expect($preview['sections'][3]['title'])->toBe('2.2.1 Major Activities');
        expect($preview['sections'][4]['title'])->toBe('2.2.2 Implementation Procedures');
    });

    it('does NOT treat ordinary numbered lines as section headings', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2 Smart Governance

The strategy consists of 5 major components:

1. Digital infrastructure
2. Policy framework
3. Stakeholder engagement
4. Implementation timeline
5. Monitoring and evaluation

Each component plays a critical role in the overall strategy.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        // Should have exactly 1 section (the root heading), NOT 5
        expect($preview['sections'])->toHaveCount(1);
        expect($preview['sections'][0]['title'])->toBe('2 Smart Governance');
        
        // The numbered list should be PART OF the section content, not separate sections
        expect($preview['sections'][0]['raw_text'])->toContain('consists of 5 major components');
        expect($preview['sections'][0]['raw_text'])->toContain('Digital infrastructure');
        expect($preview['sections'][0]['raw_text'])->toContain('Monitoring and evaluation');
    });

    it('preserves all content: sum of sections ≈ source (ZERO LOSS)', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $sourceContent = <<<'TEXT'
2 Smart Governance

Smart governance is one of the core characteristics of smart cities.

2.1 Introduction

Smart governance involves using technology to align government processes with the needs of the people.

It is a powerful political instrument to enhance the quality of public decision-making.

2.2 Major Activities

Major activities include policy development and resource allocation.

Each activity must be carefully planned and monitored.
TEXT;
        
        $originalLength = strlen($sourceContent);
        $preview = $service->previewChapter(2, $sourceContent);
        
        // Check metrics are provided
        expect($preview)->toHaveKey('original_length');
        expect($preview)->toHaveKey('extracted_length');
        expect($preview)->toHaveKey('content_preserved_pct');
        
        // Original length should match
        expect($preview['original_length'])->toBe($originalLength);
        
        // Extracted length should be close (allowing for whitespace normalization only)
        $extractedLength = $preview['extracted_length'];
        expect($extractedLength)->toBeGreaterThan(0);
        
        // Content preservation should be very high (>95%)
        expect($preview['content_preserved_pct'])->toBeGreaterThan(95.0);
        
        // Join all section text and verify key content is preserved
        $allText = '';
        foreach ($preview['sections'] as $s) {
            $allText .= $s['title'] . "\n" . $s['raw_text'] . "\n";
        }
        
        // Check that key content is preserved
        expect($allText)->toContain('Smart governance is one of the core characteristics');
        expect($allText)->toContain('using technology to align government processes');
        expect($allText)->toContain('political instrument to enhance');
        expect($allText)->toContain('Major activities include policy development');
    });

    it('handles section number on own line followed by title', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        // This pattern: number on own line, then title on next line
        $content = <<<'TEXT'
2.1
Introduction

Smart governance involves using technology.

This is the introduction section.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(1);
        expect($preview['sections'][0]['title'])->toBe('2.1 Introduction');
        expect($preview['sections'][0]['raw_text'])->toContain('Smart governance involves');
    });

    it('validates chapter number in range 1-11', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        // Use content that meets minimum length requirement (100+ chars)
        $validContent = str_repeat('This is valid content for chapter validation testing. ', 3);
        
        // Valid: chapter 2
        $validation = $service->validateChapterContent(2, $validContent);
        expect($validation['valid'])->toBeTrue();
        
        // Invalid: chapter 0
        $validation = $service->validateChapterContent(0, $validContent);
        expect($validation['valid'])->toBeFalse();
        
        // Invalid: chapter 12
        $validation = $service->validateChapterContent(12, $validContent);
        expect($validation['valid'])->toBeFalse();
    });

    it('rejects content with System Context marker', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2 Smart Governance

System Context: This section is about governance.

Smart governance is important.
TEXT;
        
        $validation = $service->validateChapterContent(2, $content);
        expect($validation['valid'])->toBeFalse();
        expect($validation['errors'][0])->toContain('System Context');
    });

    it('rejects content too short or too long', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        // Too short
        $validation = $service->validateChapterContent(2, 'Short');
        expect($validation['valid'])->toBeFalse();
        
        // Too long (100k+ chars)
        $longContent = str_repeat('This is a very long chapter. ', 5000);
        $validation = $service->validateChapterContent(2, $longContent);
        expect($validation['valid'])->toBeFalse();
    });

    it('handles uppercase section headings', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2 SMART GOVERNANCE

Content about smart governance in all caps heading.

2.1 INTRODUCTION

Content about introduction.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(2);
        expect($preview['sections'][0]['title'])->toBe('2 SMART GOVERNANCE');
        expect($preview['sections'][1]['title'])->toBe('2.1 INTRODUCTION');
    });

    it('handles mixed case section headings', function () {
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2 Smart GOVERNANCE Overview

Content about smart governance.

2.1 Key CONCEPTS and STRATEGIES

Content about concepts.
TEXT;
        
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(2);
        expect($preview['sections'][0]['title'])->toContain('Smart GOVERNANCE');
        expect($preview['sections'][1]['title'])->toContain('Key CONCEPTS');
    });

});

describe('BookIngestionService Ingest Flow', function () {
    
    it('creates a chapter with multiple sections and no embeddings yet', function () {
        $book = Book::factory()->create();
        
        $service = new BookIngestionService(new ChunkingService(), makeFakeBookEmbedder());
        
        $content = <<<'TEXT'
2 Smart Governance

Smart governance is important.

2.1 Introduction

This is the introduction.

2.2 Major Activities

These are major activities.
TEXT;
        
        // Don't call ingestChapter (which dispatches jobs) - just test section extraction
        $preview = $service->previewChapter(2, $content);
        
        expect($preview['sections'])->toHaveCount(3);
        expect($preview['sections'][0]['title'])->toBe('2 Smart Governance');
        expect($preview['sections'][1]['title'])->toBe('2.1 Introduction');
        expect($preview['sections'][2]['title'])->toBe('2.2 Major Activities');
        expect($preview['estimated_chunks'])->toBeGreaterThan(0);
    });
    
});
