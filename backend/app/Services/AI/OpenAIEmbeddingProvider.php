<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI text-embedding-3-* adapter (Req 8.1 — alternate provider).
 * Swap in by setting AI_EMBEDDING_PROVIDER=openai in .env.
 */
class OpenAIEmbeddingProvider implements EmbeddingProviderInterface
{
    private Client $client;
    private string $model;
    private int    $dimension;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('ai.openai.base_url', 'https://api.openai.com/v1'),
            'timeout'  => config('ai.openai.timeout', 30),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('ai.openai.api_key'),
                'Content-Type'  => 'application/json',
            ],
        ]);

        $this->model     = config('ai.openai.model', 'text-embedding-3-small');
        $this->dimension = (int) config('ai.openai.dimension', 1536);
    }

    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $attempts = config('ai.retry.times', 3);
        $backoff  = config('ai.retry.backoff', [500, 1000, 2000]);
        $lastException = null;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $response = $this->client->post('/embeddings', [
                    'json' => [
                        'input' => $texts,
                        'model' => $this->model,
                    ],
                ]);

                $body = json_decode((string) $response->getBody(), true);

                // OpenAI returns { data: [ { embedding: [...] }, ... ] }
                // Sort by index to preserve input order
                $data = $body['data'];
                usort($data, fn ($a, $b) => $a['index'] <=> $b['index']);

                return array_column($data, 'embedding');

            } catch (ServerException | ConnectException $e) {
                $lastException = $e;

                if ($attempt < $attempts - 1) {
                    $sleepMs = $backoff[$attempt] ?? 2000;
                    usleep($sleepMs * 1000);
                    Log::warning('OpenAIEmbeddingProvider: retrying', ['attempt' => $attempt + 1]);
                }
            } catch (\Throwable $e) {
                throw new AiProviderException(
                    'OpenAI embedding failed: ' . $e->getMessage(),
                    'openai',
                    $e
                );
            }
        }

        throw new AiProviderException(
            'OpenAI embedding failed after ' . $attempts . ' attempts.',
            'openai',
            $lastException
        );
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }
}
