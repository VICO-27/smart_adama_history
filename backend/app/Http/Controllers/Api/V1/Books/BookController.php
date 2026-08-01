<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function index(): JsonResponse
    {
        $books = Book::where('status', 'published')
            ->with(['chapters.sections'])
            ->latest()
            ->get();

        return response()->json([
            'books' => BookResource::collection($books),
        ]);
    }
}