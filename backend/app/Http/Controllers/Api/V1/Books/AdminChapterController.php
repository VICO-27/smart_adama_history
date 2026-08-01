<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Http\Requests\Books\StoreChapterRequest;
use App\Http\Requests\Books\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Jobs\IngestChapterJob;
use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;

/**
 * Admin chapter management (Req 3.2, 3.3, 3.4, 3.6)
 */
class AdminChapterController extends Controller
{
    /**
     * POST /admin/books/{book}/chapters
     */
    public function store(StoreChapterRequest $request, Book $book): JsonResponse
    {
        // Default order to end of list if not provided
        $order = $request->order
            ?? ($book->chapters()->max('order') ?? 0) + 1;

        $chapter = $book->chapters()->create([
            'title'            => $request->title,
            'order'            => $order,
            'ingestion_status' => 'draft',
        ]);

        return response()->json([
            'chapter' => new ChapterResource($chapter),
        ], 201);
    }

    /**
     * PATCH /admin/chapters/{chapter}
     * Editing an already-ingested chapter marks it for re-ingestion (Req 3.4).
     */
    public function update(UpdateChapterRequest $request, Chapter $chapter): JsonResponse
    {
        $wasReady = $chapter->ingestion_status === 'ready';

        $chapter->update($request->validated());

        // If it was already ingested, flag it so sections can be re-ingested
        if ($wasReady && $request->has('title')) {
            $chapter->update(['ingestion_status' => 'draft']);
        }

        return response()->json([
            'chapter' => new ChapterResource($chapter->fresh()->load('sections')),
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/publish
     * Enqueue ingestion job without blocking the HTTP response (Req 3.3).
     */
    public function publish(Chapter $chapter): JsonResponse
    {
        // Guard: chapter must have at least one section with text to ingest
        if ($chapter->sections()->whereNotNull('raw_text')->count() === 0) {
            return response()->json([
                'error' => [
                    'code'    => 'NO_CONTENT',
                    'message' => 'Chapter must have at least one section with content before publishing.',
                ],
            ], 422);
        }

        $chapter->update(['ingestion_status' => 'queued']);

        // Dispatch async — returns immediately (Req 3.3)
        IngestChapterJob::dispatch($chapter->id);

        return response()->json([
            'message'          => 'Ingestion queued successfully.',
            'ingestion_status' => 'queued',
        ]);
    }
}
