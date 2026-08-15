<?php

namespace App\Services\RAG;

use App\Models\Book;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Retrieves the most relevant content chunks for a query using
 * pgvector cosine distance (<=> operator).
 */
class RetrievalService
{
    public function __construct(
        private readonly EmbeddingProviderInterface $embedder,
    ) {
    }

    /**
     * Retrieve chunks strictly scoped to a specific chapter (if provided),
     * or globally scoped to the Canonical Book (to ignore duplicate seeders).
     */
    public function retrieve(string $query, int $k = 5, ?float $threshold = null, ?string $chapterId = null): array
    {
        $threshold = $threshold ?? (float) config('ai.rag.similarity_threshold', 0.35);
        $configK   = (int) config('ai.rag.top_k', 5);
        $k         = min(max(1, $k), max($configK, 30));

        $queryVector = $this->embedder->embed($query);
        $vectorStr   = '[' . implode(',', $queryVector) . ']';

        // 1. Resolve Scope to prevent cross-contamination
        $canonicalBook = Book::canonical();
        $bookId = $canonicalBook ? $canonicalBook->id : null;

        $bindings = [$vectorStr, $vectorStr, $threshold];
        
        $scopeSql = '';
        if ($chapterId) {
            // Strict Chapter Scope (Study Mode)
            $scopeSql = 'AND ch.id = ?';
            $bindings[] = $chapterId;
        } elseif ($bookId) {
            // Strict Canonical Book Scope (Global Search)
            $scopeSql = 'AND ch.book_id = ?';
            $bindings[] = $bookId;
        } else {
            // Fallback
            $scopeSql = 'AND ch.order >= 1 AND ch.order <= 11';
        }

        $bindings[] = $vectorStr;
        $bindings[] = $k;

        try {
            $rows = DB::select(
                <<<SQL
                SELECT
                    cc.id,
                    cc.chunk_text,
                    cc.section_id,
                    cc.token_count,
                    (1 - (cc.embedding <=> ?::vector)) AS similarity,
                    s.title  AS section_title,
                    s.chapter_id,
                    ch.title AS chapter_title
                FROM content_chunks cc
                JOIN sections  s  ON s.id  = cc.section_id
                JOIN chapters  ch ON ch.id = s.chapter_id
                WHERE cc.embedding_status = 'ready'
                  AND cc.embedding IS NOT NULL
                  AND (1 - (cc.embedding <=> ?::vector)) >= ?
                  $scopeSql
                ORDER BY cc.embedding <=> ?::vector
                LIMIT ?
                SQL,
                $bindings
            );
        } catch (\Throwable $e) {
            Log::error('RetrievalService: pgvector query failed', [
                'error'     => $e->getMessage(),
                'query'     => substr($query, 0, 80),
                'threshold' => $threshold,
                'chapterId' => $chapterId,
                'k'         => $k,
            ]);
            return ['chunks' => [], 'grounded' => false];
        }

        if (empty($rows)) {
            Log::info('RetrievalService: no chunks above threshold', [
                'query'     => substr($query, 0, 80),
                'threshold' => $threshold,
                'chapterId' => $chapterId,
                'k'         => $k,
            ]);
            return ['chunks' => [], 'grounded' => false];
        }

        $chunks = array_map(fn ($row) => [
            'id'            => $row->id,
            'chunk_text'    => $row->chunk_text,
            'similarity'    => round((float) $row->similarity, 4),
            'section_id'    => $row->section_id,
            'section_title' => $row->section_title,
            'chapter_id'    => $row->chapter_id,
            'chapter_title' => $row->chapter_title,
        ], $rows);

        Log::info('RetrievalService: retrieved chunks', [
            'query'      => substr($query, 0, 80),
            'count'      => count($chunks),
            'top_score'  => $chunks[0]['similarity'] ?? null,
            'chapter_id' => $chapterId,
        ]);

        return ['chunks' => $chunks, 'grounded' => true];
    }
}