<?php

namespace App\Services\AI\Contracts;

/**
 * Provider-agnostic embedding contract (Req 4.2, 8.1).
 * Swap Voyage ↔ OpenAI by changing AI_EMBEDDING_PROVIDER in .env.
 */
interface EmbeddingProviderInterface
{
    /**
     * Embed a single text string.
     *
     * @return float[]  Dense vector of floats with length = configured dimension
     * @throws \App\Exceptions\AiProviderException on permanent failure
     */
    public function embed(string $text): array;

    /**
     * Embed multiple texts in one API call (batched).
     *
     * @param  string[]  $texts
     * @return float[][] Array of vectors, indexed the same as $texts
     * @throws \App\Exceptions\AiProviderException on permanent failure
     */
    public function embedBatch(array $texts): array;

    /**
     * The dimension of vectors produced by this provider.
     */
    public function getDimension(): int;
}
