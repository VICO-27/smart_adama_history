<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\ContentChunk;
use App\Services\RAG\BookIngestionService;
use Illuminate\Http\JsonResponse;

/**
 * Admin Book Ingestion Controller
 *
 * Provides API for manual chapter content entry and ingestion.
 *
 * Routes:
 *   GET    /admin/book-ingestion              - List book ingestion status
 *   PUT    /admin/chapters/{chapter}          - Update chapter content (save draft)
 *   POST   /admin/chapters/{chapter}/validate - Validate chapter content
 *   POST   /admin/chapters/{chapter}/preview  - Preview ingestion
 *   POST   /admin/chapters/{chapter}/ingest   - Ingest chapter
 *   POST   /admin/chapters/{chapter}/retry    - Retry failed chunks
 *   GET    /admin/chapters/{chapter}/status   - Get chapter status
 *   POST   /admin/books/{book}/verify         - Verify book ingestion
 */
class AdminBookIngestionController extends Controller
{
    public function __construct(
        private readonly BookIngestionService $ingestionService,
    ) {
    }

    /**
     * GET /admin/book-ingestion
     * List book ingestion status
     */
    public function index(): JsonResponse
    {
        $book = Book::first();
        if (!$book) {
            return response()->json([
                'book' => null,
                'canonical_chapters' => $this->ingestionService->getCanonicalChapters(),
                'chapters' => [],
                'verification' => null,
            ]);
        }

        // Ensure all 11 canonical chapters exist
        $this->ingestionService->ensureCanonicalChapters($book);

        // Load chapters with their status
        $chapters = Chapter::where('book_id', $book->id)
            ->orderBy('order')
            ->get()
            ->map(function ($chapter) {
                $sectionCount = $chapter->sections()->count();
                $chunkCount = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))->count();
                $readyChunks = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))
                    ->where('embedding_status', 'ready')->count();
                $failedChunks = ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))
                    ->where('embedding_status', 'failed')->count();

                return [
                    'id' => $chapter->id,
                    'number' => $chapter->order,
                    'title' => $chapter->title,
                    'status' => $chapter->ingestion_status,
                    'has_content' => !empty($chapter->content),
                    'section_count' => $sectionCount,
                    'chunk_count' => $chunkCount,
                    'ready_chunks' => $readyChunks,
                    'failed_chunks' => $failedChunks,
                ];
            });

        $verification = $this->ingestionService->verifyBook($book);

        return response()->json([
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
            ],
            'canonical_chapters' => $this->ingestionService->getCanonicalChapters(),
            'chapters' => $chapters,
            'verification' => $verification,
        ]);
    }

    /**
     * PUT /admin/chapters/{chapter}
     * Save chapter content (draft or publish)
     */
    public function updateChapter($chapterId): JsonResponse
    {
        try {
            $chapter = Chapter::findOrFail($chapterId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Chapter not found',
                ],
            ], 404);
        } catch (\Exception $e) {
            // Handle invalid UUID format
            return response()->json([
                'error' => [
                    'code' => 'INVALID_ID',
                    'message' => 'Invalid chapter ID format',
                ],
            ], 404);
        }

        $content = request()->input('content');
        $publish = request()->input('publish', false);

        if ($content === null) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Content is required',
                ],
            ], 422);
        }

        $chapter->update(['content' => $content]);

        if ($publish) {
            return $this->ingestChapter($chapter);
        }

        return response()->json([
            'chapter' => $chapter->fresh(),
            'message' => 'Draft saved',
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/validate
     * Validate chapter content
     */
    public function validateChapter($chapterId): JsonResponse
    {
        $chapter = Chapter::find($chapterId);

        if (!$chapter) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Chapter not found',
                ],
            ], 404);
        }

        $content = request()->input('content');
        if ($content === null) {
            // Use existing content
            $content = $chapter->content ?? '';
        }

        $validation = $this->ingestionService->validateChapterContent($chapter->order, $content);

        return response()->json([
            'valid' => $validation['valid'],
            'chapter_number' => $chapter->order,
            'chapter_title' => $chapter->title,
            'errors' => $validation['errors'],
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/preview
     * Preview chapter ingestion (no embedding)
     */
    public function previewChapter($chapterId): JsonResponse
    {
        $chapter = Chapter::find($chapterId);

        if (!$chapter) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Chapter not found',
                ],
            ], 404);
        }

        $content = request()->input('content');
        if ($content === null) {
            $content = $chapter->content ?? '';
        }

        $preview = $this->ingestionService->previewChapter($chapter->order, $content);

        return response()->json([
            'chapter_number' => $chapter->order,
            'chapter_title' => $chapter->title,
            'preview' => $preview,
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/ingest
     * Ingest chapter content
     */
    public function ingestChapter(Chapter $chapter): JsonResponse
    {
        $content = $chapter->content ?? '';

        if (empty(trim($content))) {
            return response()->json([
                'error' => [
                    'code' => 'NO_CONTENT',
                    'message' => 'Chapter has no content to ingest.',
                ],
            ], 422);
        }

        $book = Book::first();
        if (!$book) {
            return response()->json([
                'error' => [
                    'code' => 'NO_BOOK',
                    'message' => 'No book found. Create a book first.',
                ],
            ], 404);
        }

        $result = $this->ingestionService->ingestChapter($book, $chapter->order, $content);

        if (!$result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $result['errors'],
                ],
            ], 422);
        }

        return response()->json([
            'chapter' => $chapter->fresh(),
            'message' => $result['message'],
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/retry
     * Retry failed chunks
     */
    public function retryFailed(Chapter $chapter): JsonResponse
    {
        $result = $this->ingestionService->retryFailedChunks($chapter);

        if (!$result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'NO_FAILURES',
                    'message' => $result['message'],
                ],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
        ]);
    }

    /**
     * GET /admin/chapters/{chapter}/status
     * Get chapter status
     */
    public function getChapterStatus(Chapter $chapter): JsonResponse
    {
        $status = $this->ingestionService->getChapterStatus($chapter);

        return response()->json([
            'chapter' => $status,
        ]);
    }

    /**
     * POST /admin/books/{book}/verify
     * Verify book ingestion completeness
     */
    public function verifyBook(Book $book): JsonResponse
    {
        $verification = $this->ingestionService->verifyBook($book);

        return response()->json([
            'verification' => $verification,
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/preview-structured
     * Preview chapter ingestion using stored sections directly (no regex extraction).
     * Does NOT call Voyage AI.
     */
    public function previewStructured(Chapter $chapter): JsonResponse
    {
        $sections = $chapter->sections()->whereNotNull('raw_text')->orderBy('order')->get();

        if ($sections->count() === 0) {
            return response()->json([
                'error' => [
                    'code' => 'NO_SECTIONS',
                    'message' => 'Chapter has no sections to preview.',
                ],
            ], 422);
        }

        $sectionData = [];
        $totalCharacters = 0;
        $totalWords = 0;

        foreach ($sections as $section) {
            $text = $section->raw_text ?: '';
            $charCount = strlen($text);
            $wordCount = str_word_count($text);
            
            $totalCharacters += $charCount;
            $totalWords += $wordCount;

            $sectionData[] = [
                'section_number' => $section->section_number,
                'title' => $section->title,
                'order' => $section->order,
                'raw_text' => $text,
                'character_count' => $charCount,
                'word_count' => $wordCount,
            ];
        }

        return response()->json([
            'chapter_number' => $chapter->order,
            'chapter_title' => $chapter->title,
            'sections' => $sectionData,
            'summary' => [
                'section_count' => count($sectionData),
                'total_characters' => $totalCharacters,
                'total_words' => $totalWords,
            ],
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/ingest-structured
     * Ingest chapter using stored sections directly (no regex extraction).
     * Chunks each section and enqueues embeddings.
     */
    public function ingestStructured(Chapter $chapter): JsonResponse
    {
        $sections = $chapter->sections()->whereNotNull('raw_text')->orderBy('order')->get();

        if ($sections->count() === 0) {
            return response()->json([
                'error' => [
                    'code' => 'NO_SECTIONS',
                    'message' => 'Chapter has no sections to ingest.',
                ],
            ], 422);
        }

        // Verify all sections have content
        foreach ($sections as $section) {
            if (empty(trim($section->raw_text))) {
                return response()->json([
                    'error' => [
                        'code' => 'EMPTY_SECTION',
                        'message' => "Section '{$section->title}' has empty content.",
                    ],
                ], 422);
            }
        }

        // Clear existing chunks for this chapter (for re-ingestion)
        \App\Models\ContentChunk::whereHas('section', fn($q) => $q->where('chapter_id', $chapter->id))->delete();

        // Create chunks for each section
        $chunkService = app(\App\Services\RAG\ChunkingService::class);
        $sectionService = app(\App\Services\RAG\BookIngestionService::class);
        $book = \App\Models\Book::first();

        if (!$book) {
            return response()->json([
                'error' => [
                    'code' => 'NO_BOOK',
                    'message' => 'No book found.',
                ],
            ], 404);
        }

        $totalChunks = 0;

        foreach ($sections as $section) {
            $chunks = $chunkService->chunk($section->raw_text, $section->id);
            $totalChunks += count($chunks);
        }

        // Update chapter status to queued
        $chapter->update(['ingestion_status' => 'queued']);

        // Dispatch ingestion job
        \App\Jobs\IngestChapterJob::dispatch($chapter->id);

        return response()->json([
            'chapter' => $chapter->fresh(),
            'message' => 'Chapter queued for structured ingestion',
            'sections_count' => $sections->count(),
            'chunks_count' => $totalChunks,
        ]);
    }
}