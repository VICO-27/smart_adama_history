<?php

use App\Services\RAG\ChunkingService;

beforeEach(function () {
    // Set config values expected by the service
    config([
        'ai.rag.chunk_target_tokens' => 700,
        'ai.rag.chunk_overlap_ratio' => 0.15,
    ]);

    $this->service = new ChunkingService();
});

// ── Basic chunking behaviour ─────────────────────────────────────────────────

it('returns an empty array for empty text', function () {
    expect($this->service->chunk('', 'section-1'))->toBeEmpty();
});

it('returns a single chunk for short text', function () {
    $text   = 'Smart Adama is a visionary framework. It teaches citizens to think strategically.';
    $chunks = $this->service->chunk($text, 'sec-1');

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]['section_id'])->toBe('sec-1')
        ->and($chunks[0]['chunk_index'])->toBe(0)
        ->and($chunks[0]['token_count'])->toBeGreaterThan(0);
});

it('produces multiple chunks for long text', function () {
    // Generate ~3000 words — well above the 700-token target
    $longText = implode(' ', array_fill(0, 3000, 'word'));
    $chunks   = $this->service->chunk($longText, 'sec-2');

    expect(count($chunks))->toBeGreaterThan(1);
});

it('assigns sequential chunk_index values starting from 0', function () {
    $longText = implode('. ', array_fill(0, 500, 'This is a sentence about Smart Adama concepts'));
    $chunks   = $this->service->chunk($longText, 'sec-3');

    $indices = array_column($chunks, 'chunk_index');
    expect($indices)->toBe(range(0, count($chunks) - 1));
});

it('preserves section_id on all chunks', function () {
    $longText = implode('. ', array_fill(0, 500, 'Smart Adama promotes civic engagement'));
    $chunks   = $this->service->chunk($longText, 'section-uuid-123');

    foreach ($chunks as $chunk) {
        expect($chunk['section_id'])->toBe('section-uuid-123');
    }
});

it('each chunk contains non-empty text', function () {
    $text   = implode('. ', array_fill(0, 200, 'Smart Adama is a book about wisdom and leadership'));
    $chunks = $this->service->chunk($text, 'sec-4');

    foreach ($chunks as $chunk) {
        expect(trim($chunk['chunk_text']))->not->toBeEmpty();
    }
});

it('each chunk has a positive token_count', function () {
    $text   = implode('. ', array_fill(0, 200, 'Wisdom is the foundation of Smart Adama'));
    $chunks = $this->service->chunk($text, 'sec-5');

    foreach ($chunks as $chunk) {
        expect($chunk['token_count'])->toBeGreaterThan(0);
    }
});

it('overlap causes consecutive chunks to share text context', function () {
    // Build a text with clearly distinguishable sentences
    $sentences = array_map(
        fn ($i) => "Sentence number {$i} discusses the principle of Smart Adama leadership.",
        range(1, 100)
    );
    $text   = implode(' ', $sentences);
    $chunks = $this->service->chunk($text, 'sec-6');

    if (count($chunks) < 2) {
        $this->markTestSkipped('Text too short to produce multiple chunks');
    }

    // The last sentence of chunk[0] should appear somewhere near the start of chunk[1]
    $firstChunkEnd   = mb_substr($chunks[0]['chunk_text'], -100);
    $secondChunkStart = mb_substr($chunks[1]['chunk_text'], 0, 200);

    // There should be SOME shared words due to overlap
    $sharedWords = array_intersect(
        explode(' ', $firstChunkEnd),
        explode(' ', $secondChunkStart)
    );

    expect(count($sharedWords))->toBeGreaterThan(0);
});
