<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Services\RAG\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Entry-point for the async ingestion pipeline (Req 3.3, 4.1).
 *
 * Flow:
 *  1. Mark chapter as processing.
 *  2. For each section, dispatch a GenerateChunkEmbeddingJob (fan-out).
 *  3. On completion all sections are ingested independently + retried if needed.
 *
 * The chapter status is set to `ready` by a separate listener once all
 * section jobs complete (tracked via job batching or simple count check).
 * For v1 we keep it simple: set ready after dispatching all, the status
 * reflects "queued for embedding". A more accurate status is updated
 * per-chunk in GenerateChunkEmbeddingJob.
 */
class IngestChapterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public readonly string $chapterId)
    {
    }

    public function handle(): void
    {
        $chapter = Chapter::with('sections')->find($this->chapterId);

        if (! $chapter) {
            Log::warning('IngestChapterJob: chapter not found', ['id' => $this->chapterId]);
            return;
        }

        $chapter->update(['ingestion_status' => 'processing']);

        $sections = $chapter->sections()->whereNotNull('raw_text')->get();

        if ($sections->isEmpty()) {
            $chapter->update(['ingestion_status' => 'ready']);
            return;
        }

        foreach ($sections as $section) {
            GenerateChunkEmbeddingJob::dispatch($section->id, $this->chapterId);
        }

        Log::info('IngestChapterJob: dispatched section jobs', [
            'chapter_id'    => $this->chapterId,
            'section_count' => $sections->count(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Chapter::where('id', $this->chapterId)
            ->update(['ingestion_status' => 'failed']);

        Log::error('IngestChapterJob failed permanently', [
            'chapter_id' => $this->chapterId,
            'error'      => $e->getMessage(),
        ]);
    }
}
