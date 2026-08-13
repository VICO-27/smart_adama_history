<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Http\Requests\Books\StoreBookRequest;
use App\Http\Resources\BookResource;
use App\Http\Resources\ChapterResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Admin book management (Req 3.1, 3.2, 3.6)
 */
class AdminBookController extends Controller
{
    /**
     * POST /admin/books
     * Upload manuscript file (optional), create Book in draft status.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $filePath = null;
        $fileType = null;

        // Handle both 'file' and 'manuscript' field names for compatibility
        $fileField = $request->hasFile('file') ? 'file' : 'manuscript';
        
        if ($request->hasFile($fileField)) {
            $file     = $request->file($fileField);
            $filePath = $file->store('manuscripts', 'local');
            $fileType = $file->getClientOriginalExtension();
        }

        $book = Book::create([
            'title'            => $request->title,
            'status'           => 'draft',
            'source_file_path' => $filePath,
            'source_file_type' => $fileType,
        ]);

        return response()->json([
            'book' => new BookResource($book),
        ], 201);
    }

    /**
     * GET /admin/books/{book}
     * Book detail with nested chapters + per-chapter ingestion_status.
     */
    public function show(Book $book): JsonResponse
    {
        $book->load(['chapters.sections']);

        return response()->json([
            'book' => array_merge(
                (new BookResource($book))->toArray(request()),
                [
                    'source_file_path' => $book->source_file_path,
                    'source_file_type' => $book->source_file_type,
                    'chapters'         => ChapterResource::collection($book->chapters),
                ]
            ),
        ]);
    }
}