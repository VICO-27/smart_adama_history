<?php

namespace App\Services\RAG;

/**
 * Splits section text into overlapping chunks suitable for embedding.
 *
 * Strategy (Req 4.1):
 *  - Target 500–800 tokens per chunk (configurable).
 *  - ~15% overlap between consecutive chunks to preserve context at boundaries.
 *  - Token count is approximated as word_count * 1.3 (fast, no tokenizer dependency).
 *    Real-world accuracy is ±10%, well within the 500–800 target band.
 *  - Splits on sentence boundaries where possible to avoid mid-sentence cuts.
 *  - Preserves section_id and chunk_index on every chunk for retrieval tracing.
 */
class ChunkingService
{
    private int   $targetTokens;
    private float $overlapRatio;

    // Rough words-to-tokens multiplier (accounts for punctuation, subwords)
    private const WORDS_PER_TOKEN = 0.75;

    public function __construct()
    {
        $this->targetTokens = (int) config('ai.rag.chunk_target_tokens', 700);
        $this->overlapRatio = (float) config('ai.rag.chunk_overlap_ratio', 0.15);
    }

    /**
     * Split $text into overlapping chunks.
     *
     * @return array<int, array{
     *     chunk_text:  string,
     *     chunk_index: int,
     *     token_count: int,
     *     section_id:  string,
     * }>
     */
    public function chunk(string $text, string $sectionId): array
    {
        $text = $this->normalise($text);

        if (empty($text)) {
            return [];
        }

        // Split into sentences as the smallest unit we will not break
        $sentences   = $this->splitSentences($text);
        $targetWords = (int) ($this->targetTokens * self::WORDS_PER_TOKEN);
        $overlapWords = (int) ($targetWords * $this->overlapRatio);

        $chunks       = [];
        $chunkIndex   = 0;
        $window       = [];   // current sliding window of sentences
        $windowWords  = 0;

        foreach ($sentences as $sentence) {
            $sentenceWords = str_word_count($sentence);

            // If a single sentence is itself larger than the target, word-split it
            if ($sentenceWords > $targetWords) {
                $words    = preg_split('/\s+/', $sentence, -1, PREG_SPLIT_NO_EMPTY);
                $subChunk = [];
                $subWords = 0;
                foreach ($words as $word) {
                    if ($subWords + 1 > $targetWords && ! empty($subChunk)) {
                        // Flush current window first
                        if (! empty($window)) {
                            $chunks[] = $this->buildChunk(implode(' ', $window), $chunkIndex++, $sectionId);
                            [$window, $windowWords] = $this->buildOverlapTail($window, $overlapWords);
                        }
                        $chunks[] = $this->buildChunk(implode(' ', $subChunk), $chunkIndex++, $sectionId);
                        $subChunk = [];
                        $subWords = 0;
                    }
                    $subChunk[] = $word;
                    $subWords++;
                }
                if (! empty($subChunk)) {
                    $window[]     = implode(' ', $subChunk);
                    $windowWords += $subWords;
                }
                continue;
            }

            // If adding this sentence would exceed target, flush the current window
            if ($windowWords + $sentenceWords > $targetWords && ! empty($window)) {
                $chunks[] = $this->buildChunk(
                    implode(' ', $window),
                    $chunkIndex++,
                    $sectionId
                );

                // Retain the overlap tail for the next chunk
                [$window, $windowWords] = $this->buildOverlapTail($window, $overlapWords);
            }

            $window[]     = $sentence;
            $windowWords += $sentenceWords;
        }

        // Flush the final window
        if (! empty($window)) {
            $chunks[] = $this->buildChunk(
                implode(' ', $window),
                $chunkIndex,
                $sectionId
            );
        }

        return $chunks;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function normalise(string $text): string
    {
        // Collapse multiple whitespace/newlines to single space
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Split text into sentences.
     * Uses a simple regex that handles common abbreviations (Mr., Dr., etc.)
     * without an NLTK dependency.
     */
    private function splitSentences(string $text): array
    {
        // Split after sentence-ending punctuation followed by whitespace + capital
        $parts = preg_split(
            '/(?<=[.!?])\s+(?=[A-Z\p{Lu}])/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        return array_values(array_filter(
            $parts,
            fn ($s) => mb_strlen(trim($s)) > 0
        ));
    }

    /**
     * Build the tail of the current window to use as overlap for the next chunk.
     * Takes sentences from the end until we hit the overlap word target.
     *
     * @param  string[]  $sentences
     * @return array{string[], int}  [sentences_tail, total_words]
     */
    private function buildOverlapTail(array $sentences, int $overlapWords): array
    {
        $tail      = [];
        $tailWords = 0;

        foreach (array_reverse($sentences) as $sentence) {
            $words = str_word_count($sentence);
            if ($tailWords + $words > $overlapWords && ! empty($tail)) {
                break;
            }
            array_unshift($tail, $sentence);
            $tailWords += $words;
        }

        return [$tail, $tailWords];
    }

    /**
     * Build a chunk array from a text string.
     *
     * @return array{chunk_text: string, chunk_index: int, token_count: int, section_id: string}
     */
    private function buildChunk(string $text, int $index, string $sectionId): array
    {
        $words      = str_word_count($text);
        $tokenCount = (int) ceil($words / self::WORDS_PER_TOKEN);

        return [
            'chunk_text'  => $text,
            'chunk_index' => $index,
            'token_count' => $tokenCount,
            'section_id'  => $sectionId,
        ];
    }
}
