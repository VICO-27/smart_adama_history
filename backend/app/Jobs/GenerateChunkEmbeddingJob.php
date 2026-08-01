<?php

namespace App\Jobs;

use App\Exceptions\AiProviderException;
use App\Models\Chapter;
use App\Models\ContentChunk;
use App\Models\Section;
use App\Services\RAG\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Embeds all chunks for a single section and persists them (Req 4.2, 4.4).
 *
 * Retried up to 3 times with exponential backoff on provider failures.
 * Permanent failure marks the section's chunks as `failed` and flips
 * the chapter ingestion_status to `failed` (Req 3.5, 4.4).
 */
class GenerateChunkEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 900;  // 15 minutes for free tier rate limits

    public function __construct(
        public readonly string $sectionId,
        public readonly string $chapterId,
    ) {
    }

    /**
     * Exponential backoff: 30s, 60s, 120s between retries.
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(IngestionService $ingestionService): void
    {
        $section = Section::find($this->sectionId);

        if (! $section) {
            Log::warning('GenerateChunkEmbeddingJob: section not found', [
                'section_id' => $this->sectionId,
            ]);
            return;
        }

        $ingestionService->ingestSection($section);

        // Check if all sections for this chapter are now ready
        $this->maybeMarkChapterReady();
    }

    public function failed(\Throwable $e): void
    {
        // Mark any pending chunks for this section as failed (Req 4.4)
        ContentChunk::where('section_id', $this->sectionId)
            ->where('embedding_status', 'pending')
            ->update(['embedding_status' => 'failed']);

        // Flip chapter status to failed (Req 3.5)
        Chapter::where('id', $this->chapterId)
            ->update(['ingestion_status' => 'failed']);

        Log::error('GenerateChunkEmbeddingJob failed permanently', [
            'section_id' => $this->sectionId,
            'chapter_id' => $this->chapterId,
            'error'      => $e->getMessage(),
        ]);
    }

    /**
     * Mark the chapter as `ready` once all its sections have been ingested.
     * Uses a simple count check — all sections must have at least one
     * `ready` chunk OR have no raw_text (already skipped).
     */
    private function maybeMarkChapterReady(): void
    {
        $chapter  = Chapter::with('sections.contentChunks')->find($this->chapterId);

        if (! $chapter) {
            return;
        }

        $textSections = $chapter->sections->filter(
            fn ($s) => ! empty($s->raw_text)
        );

        $allReady = $textSections->every(
            fn ($s) => $s->contentChunks->where('embedding_status', 'ready')->isNotEmpty()
        );

        if ($allReady) {
            $chapter->update([
                'ingestion_status' => 'ready',
                'ingested_at'      => now(),
            ]);

            Log::info('Chapter fully ingested and ready', [
                'chapter_id' => $this->chapterId,
            ]);
        }
    }
}
