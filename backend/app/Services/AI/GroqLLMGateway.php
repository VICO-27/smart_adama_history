<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\LLMGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Log;

/**
 * Groq LLM gateway — OpenAI-compatible REST API with SSE streaming.
 */
class GroqLLMGateway implements LLMGatewayInterface
{
    private Client $client;
    private string $model;
    private int    $maxTokens;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('ai.groq.base_url', 'https://api.groq.com/openai/v1/'),
            'timeout'  => config('ai.groq.timeout', 60),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('ai.groq.api_key'),
                'Content-Type'  => 'application/json',
                'Accept'        => 'text/event-stream',
            ],
            // FORCE cURL to stream instantly without buffering
            'curl' => [
                CURLOPT_TCP_NODELAY => true,
                CURLOPT_BUFFERSIZE => 1,
            ]
        ]);

        $this->model     = config('ai.groq.model', 'llama-3.3-70b-specdec');
        $this->maxTokens = (int) config('ai.groq.max_tokens', 1024);
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $payload = [
            'model'      => $options['model'] ?? $this->model,
            'messages'   => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'stream'     => true,
        ];

        foreach (['temperature', 'response_format', 'top_p'] as $key) {
            if (isset($options[$key])) {
                $payload[$key] = $options[$key];
            }
        }

        $attempts = config('ai.retry.times', 3);
        $backoff  = config('ai.retry.backoff', [500, 1000, 2000]);

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $response = $this->client->post('chat/completions', [
                    'json'   => $payload,
                    'stream' => true,
                ]);

                $body = $response->getBody();

                // Use Guzzle's native Utils::readLine instead of byte-by-byte
                while (!$body->eof()) {
                    $line = Utils::readLine($body);
                    $line = trim($line);

                    if (!str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $jsonStr = trim(substr($line, 6));

                    if ($jsonStr === '[DONE]') {
                        return;
                    }

                    $data    = json_decode($jsonStr, true);
                    $content = $data['choices'][0]['delta']['content'] ?? null;

                    if ($content !== null) {
                        yield $content;
                    }
                }

                return;

            } catch (ServerException $e) {
                $statusCode = $e->getResponse()?->getStatusCode();
                if ($statusCode === 429) {
                    throw new AiProviderException(
                        'Groq rate limit reached. Please wait before retrying.',
                        'groq',
                        $e
                    );
                }

                if ($attempt < $attempts - 1) {
                    usleep(($backoff[$attempt] ?? 2000) * 1000);
                    continue;
                }

                throw new AiProviderException('Groq API unavailable.', 'groq', $e);

            } catch (ConnectException $e) {
                if ($attempt < $attempts - 1) {
                    usleep(($backoff[$attempt] ?? 2000) * 1000);
                    continue;
                }
                throw new AiProviderException('Groq connection failed.', 'groq', $e);
            } catch (\Throwable $e) {
                throw new AiProviderException('Groq API error: ' . $e->getMessage(), 'groq', $e);
            }
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        $full = '';
        foreach ($this->streamChat($messages, $options) as $token) {
            $full .= $token;
        }
        return $full;
    }
}