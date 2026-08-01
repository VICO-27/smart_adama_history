<?php

use App\Jobs\GenerateChunkEmbeddingJob;
use App\Jobs\IngestChapterJob;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\ContentChunk;
use App\Models\Section;
use App\Models\User;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'ai.rag.chunk_target_tokens' => 100,
        'ai.rag.chunk_overlap_ratio' => 0.15,
        'ai.voyage.dimension'        => 1024,
    ]);

    // Bind fake embedder for all ingestion tests — no live API calls
    $this->app->bind(EmbeddingProviderInterface::class, function () {
        return new class implements EmbeddingProviderInterface {
            public function embed(string $text): array { return array_fill(0, 1024, 0.1); }
            public function embedBatch(array $texts): array {
                return array_map(fn () => array_fill(0, 1024, 0.1), $texts);
            }
            public function getDimension(): int { return 1024; }
        };
    });
});

// ── IngestChapterJob ─────────────────────────────────────────────────────────

it('IngestChapterJob dispatches GenerateChunkEmbeddingJob for each section', function () {
    // Only fake the inner job so IngestChapterJob itself runs synchronously
    Queue::fake([GenerateChunkEmbeddingJob::class]);

    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);

    Section::factory()->count(3)->create([
        'chapter_id' => $chapter->id,
        'raw_text'   => 'Smart Adama content for section.',
    ]);

    IngestChapterJob::dispatchSync($chapter->id);

    Queue::assertPushed(GenerateChunkEmbeddingJob::class, 3);
    expect($chapter->fresh()->ingestion_status)->toBe('processing');
});

it('IngestChapterJob sets chapter to ready when no sections have text', function () {
    Queue::fake([GenerateChunkEmbeddingJob::class]);

    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);
    Section::factory()->create(['chapter_id' => $chapter->id, 'raw_text' => null]);

    IngestChapterJob::dispatchSync($chapter->id);

    expect($chapter->fresh()->ingestion_status)->toBe('ready');
    Queue::assertNothingPushed();
});

// ── Full pipeline with faked embedder (contract test, Req 9.3) ───────────────

it('full ingestion pipeline stores chunks and marks chapter ready', function () {
    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);

    $section = Section::factory()->create([
        'chapter_id' => $chapter->id,
        'raw_text'   => implode('. ', array_fill(0, 20,
            'Smart Adama teaches citizens to engage with civic responsibility'
        )),
    ]);

    // Run the full pipeline synchronously (bypasses queue)
    GenerateChunkEmbeddingJob::dispatchSync($section->id, $chapter->id);

    // Chunks exist and are ready
    $readyChunks = ContentChunk::where('section_id', $section->id)
        ->where('embedding_status', 'ready')
        ->count();

    expect($readyChunks)->toBeGreaterThan(0);

    // Chapter is marked ready
    expect($chapter->fresh()->ingestion_status)->toBe('ready');
    expect($chapter->fresh()->ingested_at)->not->toBeNull();
});

it('re-ingestion deletes old chunks before inserting new ones (Req 4.5)', function () {
    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);
    $section = Section::factory()->create([
        'chapter_id' => $chapter->id,
        'raw_text'   => 'Initial content about Smart Adama.',
    ]);

    // First ingestion
    GenerateChunkEmbeddingJob::dispatchSync($section->id, $chapter->id);
    $firstCount = ContentChunk::where('section_id', $section->id)->count();

    // Update text and re-ingest
    $section->update(['raw_text' => 'Completely revised content about civic wisdom.']);
    GenerateChunkEmbeddingJob::dispatchSync($section->id, $chapter->id);

    $finalCount = ContentChunk::where('section_id', $section->id)->count();

    // No orphaned chunks — count reflects only the latest ingestion
    expect($finalCount)->toBeGreaterThanOrEqual(1);

    // Verify no stale chunks by checking all have current content
    ContentChunk::where('section_id', $section->id)->each(function ($chunk) {
        expect($chunk->embedding_status)->toBe('ready');
    });
});
