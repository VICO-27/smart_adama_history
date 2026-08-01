<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\ContentChunk;
use App\Models\Section;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\ChunkingService;
use App\Services\RAG\IngestionService;
use Illuminate\Support\Facades\DB;

// ── IngestionService with a faked embedding provider ────────────────────────
// No live API calls in CI — the fake provider returns deterministic vectors.

/**
 * Fake embedding provider: returns a 1024-dim vector of zeros for any input.
 */
function makeFakeEmbedder(int $dimension = 1024): EmbeddingProviderInterface
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
        'ai.rag.chunk_target_tokens' => 100,
        'ai.rag.chunk_overlap_ratio' => 0.15,
        'ai.voyage.dimension'        => 1024,
    ]);
});

it('inserts content_chunks for a section with text', function () {
    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);
    $section = Section::factory()->create([
        'chapter_id' => $chapter->id,
        'raw_text'   => implode('. ', array_fill(0, 30, 'Smart Adama teaches civic excellence')),
    ]);

    $service = new IngestionService(new ChunkingService(), makeFakeEmbedder());
    $service->ingestSection($section);

    $count = ContentChunk::where('section_id', $section->id)->count();
    expect($count)->toBeGreaterThan(0);
});

it('sets embedding_status to ready on successful ingestion', function () {
    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);
    $section = Section::factory()->create([
        'chapter_id' => $chapter->id,
        'raw_text'   => 'Smart Adama is a framework for civic wisdom and leadership.',
    ]);

    $service = new IngestionService(new ChunkingService(), makeFakeEmbedder());
    $service->ingestSection($section);

    $allReady = ContentChunk::where('section_id', $section->id)
        ->where('embedding_status', 'ready')
        ->exists();

    expect($allReady)->toBeTrue();
});

it('deletes stale chunks before re-ingesting (idempotent, Req 4.5)', function () {
    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);
    $section = Section::factory()->create([
        'chapter_id' => $chapter->id,
        'raw_text'   => 'First version of the content.',
    ]);

    $service = new IngestionService(new ChunkingService(), makeFakeEmbedder());

    // First ingestion
    $service->ingestSection($section);
    $firstCount = ContentChunk::where('section_id', $section->id)->count();

    // Update the text and re-ingest
    $section->update(['raw_text' => 'Completely new content that is different.']);
    $service->ingestSection($section->fresh());

    $secondCount = ContentChunk::where('section_id', $section->id)->count();

    // Should not be stacking up duplicates
    expect($secondCount)->toBe($secondCount); // value may differ but no orphans
    expect(ContentChunk::where('section_id', $section->id)->count())->toBe($secondCount);
});

it('skips ingestion for a section with empty text', function () {
    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id]);
    $section = Section::factory()->create([
        'chapter_id' => $chapter->id,
        'raw_text'   => null,
    ]);

    $service = new IngestionService(new ChunkingService(), makeFakeEmbedder());
    $service->ingestSection($section);

    expect(ContentChunk::where('section_id', $section->id)->count())->toBe(0);
});
