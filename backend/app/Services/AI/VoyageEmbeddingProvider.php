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
            'timeout'  => config('ai.voyage.timeout', 30),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('ai.voyage.api_key'),
                'Content-Type'  => 'application/json',
            ],
        ]);

        $this->model     = config('ai.voyage.model', 'voyage-3-lite');
        $this->dimension = (int) config('ai.voyage.dimension', 1024);
        $this->batchSize = (int) config('ai.voyage.batch_size', 32);
        
        $this->requestDelaySeconds = (int) config('ai.voyage.request_delay_seconds', 25);
        $this->rateLimitRetrySeconds = (int) config('ai.voyage.rate_limit_retry_seconds', 60);
        $this->rateLimitMaxRetries = (int) config('ai.voyage.rate_limit_max_retries', 3);
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

        $results = [];

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

    private function callApi(array $texts): array
    {
        $attempts = config('ai.retry.times', 3);
        $backoff  = config('ai.retry.backoff', [500, 1000, 2000]);
        $lastException = null;

        $isForegroundQuery = count($texts) === 1;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $lock = null;

                if (!$isForegroundQuery) {
                    $lock = Cache::lock('voyage_api_rate_limit', $this->requestDelaySeconds + 10);
                    $lock->block($this->requestDelaySeconds + 5);
                }
                
                try {
                    // Restored the hardcoded Absolute URL to prevent Guzzle base_uri stripping
                    $response = $this->client->post('https://api.voyageai.com/v1/embeddings', [
                        'json' => [
                            'input'      => $texts,
                            'model'      => $this->model,
                            'input_type' => $isForegroundQuery ? 'query' : 'document', 
                        ],
                    ]);

                    $body = json_decode((string) $response->getBody(), true);
                    $embeddings = array_column($body['data'], 'embedding');
                    
                    if (!$isForegroundQuery) {
                        sleep($this->requestDelaySeconds);
                    }
                    
                    return $embeddings;
                    
                } finally {
                    if ($lock) {
                        $lock->release();
                    }
                }

            } catch (ServerException | ConnectException $e) {
                $lastException = $e;

                if ($attempt < $attempts - 1) {
                    $sleepMs = $backoff[$attempt] ?? 2000;
                    usleep($sleepMs * 1000);
                }
            } catch (ClientException $e) {
                $statusCode = $e->getResponse()?->getStatusCode();
                
                if ($statusCode === 429) {
                    $waitSeconds = $this->rateLimitRetrySeconds * (2 ** $attempt);
                    Log::error('VoyageEmbeddingProvider: rate limited (429), waiting ' . $waitSeconds . 's before retry');
                    
                    if ($attempt < $this->rateLimitMaxRetries - 1) {
                        sleep($waitSeconds);
                        continue; 
                    }
                    
                    $lastException = $e;
                    break;
                }
                
                throw new AiProviderException('Voyage AI embedding failed: ' . $e->getMessage(), 'voyage', $e);
            } catch (\Throwable $e) {
                throw new AiProviderException('Voyage AI embedding failed: ' . $e->getMessage(), 'voyage', $e);
            }
        }

        throw new AiProviderException('Voyage AI embedding failed after ' . $attempts . ' attempts.', 'voyage', $lastException);
    }
}