<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RAG\RetrievalService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly RetrievalService $retrievalService
    ) {}

    public function search(Request $request)
    {
        $query = $request->input('q');

        if (empty(trim($query))) {
            return response()->json(['results' => [], 'ai_answer' => null]);
        }

        try {
            // Retrieve top matching chunks using pgvector cosine distance
            $retrievalResult = $this->retrievalService->retrieve($query, 4);
            $chunks = $retrievalResult['chunks'];

            if (empty($chunks)) {
                return response()->json([
                    'query' => $query,
                    'ai_answer' => "I could not find grounded information about that in the Smart Adama content.",
                    'results' => []
                ]);
            }

            // Use the top matching chunk text as the direct instant answer snippet
            $bestMatch = $chunks[0];
            $aiAnswer = trim($bestMatch['chunk_text']);
            if (mb_strlen($aiAnswer) > 300) {
                $aiAnswer = mb_substr($aiAnswer, 0, 300) . '...';
            }

            $results = array_map(function ($chunk) {
                return [
                    'id' => $chunk['id'],
                    'title' => $chunk['section_title'] ?? 'Section',
                    'chapter' => $chunk['chapter_title'] ?? 'Chapter',
                    'snippet' => mb_substr($chunk['chunk_text'], 0, 140) . '...',
                    'similarity' => $chunk['similarity'],
                    'section_id' => $chunk['section_id'],
                    'chapter_id' => $chunk['chapter_id'],
                ];
            }, $chunks);

            return response()->json([
                'query' => $query,
                'grounded' => $retrievalResult['grounded'],
                'ai_answer' => "According to {$bestMatch['chapter_title']} ({$bestMatch['section_title']}): \"{$aiAnswer}\"",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'results' => []
            ], 500);
        }
    }
}