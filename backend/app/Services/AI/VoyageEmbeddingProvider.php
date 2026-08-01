<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Voyage AI embedding provider (Req 8.1, 4.2).
 * Model: voyage-3-lite (1024-dim) by default; configurable via .env.
 * Handles rate limits (429) with exponential backoff and configurable delays.
 */
class VoyageEmbeddingProvider implements EmbeddingProviderInterface
{
    private Client $client;
    private string $model;
    private int    $dimension;
    private int    $batchSize;
    private int    $requestDelaySeconds;
    private int    $rateLimitRetrySeconds;
    private int    $rateLimitMaxRetries;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('ai.voyage.base_url'),
            'timeout'  => config('ai.voyage.timeout', 30),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('ai.voyage.api_key'),
                'Content-Type'  => 'application/json',
            ],
        ]);

        $this->model     = config('ai.voyage.model', 'voyage-3-lite');
        $this->dimension = (int) config('ai.voyage.dimension', 1024);
        $this->batchSize = (int) config('ai.voyage.batch_size', 32);
        
        // Rate limiting configuration for Voyage AI
        $this->requestDelaySeconds = (int) config('ai.voyage.request_delay_seconds', 25);
        $this->rateLimitRetrySeconds = (int) config('ai.voyage.rate_limit_retry_seconds', 60);
        $this->rateLimitMaxRetries = (int) config('ai.voyage.rate_limit_max_retries', 3);
    }

    /**
     * Embed a single string. Delegates to embedBatch for DRY consistency.
     */
    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    /**
     * Embed multiple strings in one API call (up to $batchSize at a time).
     */
    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $results = [];

        // Split into batches if needed
        foreach (array_chunk($texts, $this->batchSize) as $batch) {
            $batchResults = $this->callApi($batch);
            $results      = array_merge($results, $batchResults);
        }

        return $results;
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * Make the actual HTTP request with retry logic (Req 4.4, 10.3).
     * Retries on 5xx / connection errors, exponential backoff, max 3 times.
     * Handles 429 (rate limit) with exponential backoff and configurable delays.
     * 
     * Uses centralized cache-based rate limiting to prevent concurrent workers
     * from overwhelming the Voyage API.
     *
     * @param  string[]  $texts
     * @return float[][]
     */
    private function callApi(array $texts): array
    {
        $attempts = config('ai.retry.times', 3);
        $backoff  = config('ai.retry.backoff', [500, 1000, 2000]);
        $lastException = null;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                // Centralized rate limiting using cache lock
                // This ensures only ONE worker can make a Voyage request at a time
                $lock = Cache::lock('voyage_api_rate_limit', $this->requestDelaySeconds + 10);
                
                try {
                    // Wait to acquire the lock (blocks until previous request finishes + delay)
                    $lock->block($this->requestDelaySeconds + 5);
                    
                    Log::info('VoyageEmbeddingProvider: acquired rate limit lock', [
                        'batch_size' => count($texts),
                    ]);

                    // Using absolute URL and including input_type as required by Voyage API specs
                    $response = $this->client->post('https://api.voyageai.com/v1/embeddings', [
                        'json' => [
                            'input'      => $texts,
                            'model'      => $this->model,
                            'input_type' => 'document', 
                        ],
                    ]);

                    $body = json_decode((string) $response->getBody(), true);

                    // Voyage returns { data: [ { embedding: [...] }, ... ] }
                    $embeddings = array_column($body['data'], 'embedding');
                    
                    // Hold the lock for the configured delay to rate limit subsequent requests
                    sleep($this->requestDelaySeconds);
                    
                    return $embeddings;
                    
                } finally {
                    // Always release the lock
                    $lock->release();
                }

            } catch (ServerException | ConnectException $e) {
                $lastException = $e;

                if ($attempt < $attempts - 1) {
                    $sleepMs = $backoff[$attempt] ?? 2000;
                    usleep($sleepMs * 1000);

                    Log::warning('VoyageEmbeddingProvider: retrying on server/connection error', [
                        'attempt' => $attempt + 1,
                        'error'   => $e->getMessage(),
                    ]);
                }
            } catch (ClientException $e) {
                $statusCode = $e->getResponse()?->getStatusCode();
                
                if ($statusCode === 429) {
                    // Rate limited - wait with exponential backoff
                    $waitSeconds = $this->rateLimitRetrySeconds * (2 ** $attempt);
                    Log::error('VoyageEmbeddingProvider: rate limited (429), waiting ' . $waitSeconds . 's before retry', [
                        'attempt' => $attempt + 1,
                        'wait_seconds' => $waitSeconds,
                        'error' => $e->getMessage(),
                    ]);
                    
                    if ($attempt < $this->rateLimitMaxRetries - 1) {
                        sleep($waitSeconds);
                        continue; // Retry without consuming the normal retry counter
                    }
                    
                    $lastException = $e;
                    break; // Max rate limit retries exceeded
                }
                
                // Non-retryable client error (4xx other than 429)
                Log::error('VoyageEmbeddingProvider: non-retryable client error', [
                    'status' => $statusCode,
                    'error' => $e->getMessage(),
                ]);
                throw new AiProviderException(
                    'Voyage AI embedding failed: ' . $e->getMessage(),
                    'voyage',
                    $e
                );
            } catch (\Throwable $e) {
                Log::error('VoyageEmbeddingProvider: unexpected error', [
                    'error' => $e->getMessage(),
                ]);
                throw new AiProviderException(
                    'Voyage AI embedding failed: ' . $e->getMessage(),
                    'voyage',
                    $e
                );
            }
        }

        throw new AiProviderException(
            'Voyage AI embedding failed after ' . $attempts . ' attempts.',
            'voyage',
            $lastException
        );
    }
}