<?php

namespace App\Services\RAG;

use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Retrieves the most relevant content chunks for a query using
 * pgvector cosine distance (<=> operator).
 *
 * The <=> operator returns cosine DISTANCE (0 = identical, 2 = opposite).
 * We convert to similarity = 1 - distance so callers always work with
 * an intuitive 0–1 scale where 1 is a perfect match.
 *
 * Threshold: applied to the similarity score (not the raw distance).
 * Config: ai.rag.similarity_threshold (default 0.35 — calibrated for
 *         voyage-3-lite on Ethiopian/Amharic academic content).
 */
class RetrievalService
{
    public function __construct(
        private readonly EmbeddingProviderInterface $embedder,
    ) {
    }

    /**
     * Embed $query and return the top-$k chunks whose cosine similarity
     * exceeds $threshold.
     *
     * Returns:
     *   [
     *     'chunks'   => [...],  // empty array when nothing qualifies
     *     'grounded' => bool,   // true when at least one chunk was returned
     *   ]
     *
     * @throws \App\Exceptions\AiProviderException  when the embedding call fails
     */
    public function retrieve(string $query, int $k = 5, ?float $threshold = null): array
    {
        $threshold = $threshold ?? (float) config('ai.rag.similarity_threshold', 0.35);
        $configK   = (int) config('ai.rag.top_k', 5);
        $k         = min(max(1, $k), max($configK, 30)); // clamp: 1 ≤ k ≤ max(config, 30)

        // Embed the query using the active embedding provider
        $queryVector = $this->embedder->embed($query);
        $vectorStr   = '[' . implode(',', $queryVector) . ']';

        /*
         * pgvector cosine distance: cc.embedding <=> ?::vector
         *
         * distance = 1 - cosine_similarity  →  similarity = 1 - distance
         *
         * We filter by similarity (not distance) for human-readable thresholds.
         * ORDER BY distance ASC = ORDER BY similarity DESC (best match first).
         *
         * We pass $vectorStr four times:
         *   1. SELECT clause — compute similarity for output
         *   2. WHERE clause  — apply similarity threshold
         *   3. ORDER BY      — sort by distance (equivalent to sorting by similarity)
         *   4. (implicit) — the cast ::vector is applied per binding
         */
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
                  AND ch.order >= 1 AND ch.order <= 11
                  AND (1 - (cc.embedding <=> ?::vector)) >= ?
                ORDER BY cc.embedding <=> ?::vector
                LIMIT ?
                SQL,
                [$vectorStr, $vectorStr, $threshold, $vectorStr, $k]
            );
        } catch (\Throwable $e) {
            Log::error('RetrievalService: pgvector query failed', [
                'error'     => $e->getMessage(),
                'query'     => substr($query, 0, 80),
                'threshold' => $threshold,
                'k'         => $k,
            ]);
            // Surface as an empty result so the caller can still respond
            // with the no-context fallback rather than a 500.
            return ['chunks' => [], 'grounded' => false];
        }

        if (empty($rows)) {
            Log::info('RetrievalService: no chunks above threshold', [
                'query'     => substr($query, 0, 80),
                'threshold' => $threshold,
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
            'threshold'  => $threshold,
        ]);

        return ['chunks' => $chunks, 'grounded' => true];
    }
}
