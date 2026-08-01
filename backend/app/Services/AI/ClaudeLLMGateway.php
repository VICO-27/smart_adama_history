<?php

namespace App\Services\AI;

use App\Exceptions\AiProviderException;
use App\Services\AI\Contracts\LLMGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

/**
 * Anthropic Claude LLM gateway with SSE streaming support (Req 5.2, 10.1).
 *
 * Streaming: calls the /messages endpoint with stream=true and yields
 * content_block_delta tokens as they arrive so the ChatMessageController
 * can pipe them straight to the SSE response.
 *
 * Retry: 3 attempts, exponential backoff, 5xx/timeout only.
 * Provider error internals are never leaked to the client (Req 10.3, 16.5).
 */
class ClaudeLLMGateway implements LLMGatewayInterface
{
    private Client $client;
    private string $model;
    private int    $maxTokens;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('ai.claude.base_url', 'https://api.anthropic.com/v1'),
            'timeout'  => config('ai.claude.timeout', 60),
            'headers'  => [
                'x-api-key'         => config('ai.claude.api_key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ],
        ]);

        $this->model     = config('ai.claude.model', 'claude-3-5-sonnet-20241022');
        $this->maxTokens = (int) config('ai.claude.max_tokens', 2048);
    }

    /**
     * Stream a chat completion.
     * Yields string token deltas as they arrive from the Anthropic SSE stream.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return \Generator<int, string, mixed, void>
     */
    public function streamChat(array $messages, array $options = []): \Generator
    {
        // Separate system message (Claude API uses a dedicated system param)
        $system   = null;
        $filtered = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
            } else {
                $filtered[] = $msg;
            }
        }

        $payload = array_filter([
            'model'      => $this->model,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'system'     => $system,
            'messages'   => $filtered,
            'stream'     => true,
        ]);

        $attempts = config('ai.retry.times', 3);
        $backoff  = config('ai.retry.backoff', [500, 1000, 2000]);

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $response = $this->client->post('/messages', [
                    'json'   => $payload,
                    'stream' => true,
                ]);

                $body = $response->getBody();

                while (! $body->eof()) {
                    $line = $this->readLine($body);

                    if (! str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $data = json_decode(substr($line, 6), true);

                    if (! is_array($data)) {
                        continue;
                    }

                    // Content delta from Anthropic SSE
                    if ($data['type'] === 'content_block_delta'
                        && ($data['delta']['type'] ?? '') === 'text_delta'
                    ) {
                        yield $data['delta']['text'];
                    }

                    // Stream ended
                    if ($data['type'] === 'message_stop') {
                        return;
                    }
                }

                return; // Stream ended without message_stop

            } catch (ServerException | ConnectException $e) {
                if ($attempt < $attempts - 1) {
                    $sleepMs = $backoff[$attempt] ?? 2000;
                    usleep($sleepMs * 1000);
                    Log::warning('ClaudeLLMGateway stream: retrying', ['attempt' => $attempt + 1]);
                    continue;
                }

                throw new AiProviderException(
                    'Claude API unavailable after ' . $attempts . ' attempts.',
                    'claude',
                    $e
                );
            } catch (\Throwable $e) {
                throw new AiProviderException(
                    'Claude API error: ' . $e->getMessage(),
                    'claude',
                    $e
                );
            }
        }
    }

    /**
     * Non-streaming completion — collects the full streamed response.
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
     * Read a single SSE line from a Guzzle stream body.
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
