<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\LLMGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

/**
 * Groq LLM gateway — OpenAI-compatible REST API with SSE streaming.
 *
 * Default model: llama-3.3-70b-versatile (configurable via GROQ_MODEL in .env).
 * Configuration keys: config('ai.groq.*').
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
            ],
        ]);

        // Use faster model for chat: llama-3.3-70b-specdec (speculative decoding = faster)
        // Fallback to llama-3.3-70b-versatile if specdec is unavailable
        $this->model     = config('ai.groq.model', 'llama-3.3-70b-specdec');
        // Reduce max_tokens to 1024 for faster response times
        $this->maxTokens = (int) config('ai.groq.max_tokens', 1024);
    }

    /**
     * Stream a chat completion — yields string token deltas as they arrive.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return \Generator<int, string, mixed, void>
     */
    public function streamChat(array $messages, array $options = []): \Generator
    {
        $payload = [
            'model'      => $options['model'] ?? $this->model,
            'messages'   => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'stream'     => true,
        ];

        // Pass through any extra options (e.g. temperature, response_format)
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

                while (! $body->eof()) {
                    $line = $this->readLine($body);

                    if (! str_starts_with($line, 'data: ')) {
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
                // 429 (rate limit) — do not retry automatically; surface it
                $statusCode = $e->getResponse()?->getStatusCode();
                if ($statusCode === 429) {
                    throw new AiProviderException(
                        'Groq rate limit reached. Please wait before retrying.',
                        'groq',
                        $e
                    );
                }

                if ($attempt < $attempts - 1) {
                    $sleepMs = $backoff[$attempt] ?? 2000;
                    usleep($sleepMs * 1000);
                    Log::warning('GroqLLMGateway: retrying on server error', [
                        'attempt' => $attempt + 1,
                        'status'  => $statusCode,
                    ]);
                    continue;
                }

                throw new AiProviderException(
                    'Groq API unavailable after ' . $attempts . ' attempts.',
                    'groq',
                    $e
                );

            } catch (ConnectException $e) {
                if ($attempt < $attempts - 1) {
                    $sleepMs = $backoff[$attempt] ?? 2000;
                    usleep($sleepMs * 1000);
                    Log::warning('GroqLLMGateway: retrying on connect error', ['attempt' => $attempt + 1]);
                    continue;
                }

                throw new AiProviderException(
                    'Groq connection failed after ' . $attempts . ' attempts.',
                    'groq',
                    $e
                );

            } catch (\Throwable $e) {
                throw new AiProviderException(
                    'Groq API error: ' . $e->getMessage(),
                    'groq',
                    $e
                );
            }
        }
    }

    /**
     * Non-streaming completion — collects the full stream into a single string.
     * Used for structured generation (e.g. quiz JSON output).
     */
    public function chat(array $messages, array $options = []): string
    {
        $full = '';
        foreach ($this->streamChat($messages, $options) as $token) {
            $full .= $token;
        }
        return $full;
    }

    /**
     * Read a single SSE line from a Guzzle stream body, one byte at a time.
     */
    private function readLine($body): string
    {
        $line = '';
        while (! $body->eof()) {
            $char = $body->read(1);
            if ($char === "\n") {
                break;
            }
            $line .= $char;
        }
        return rtrim($line, "\r");
    }
}
