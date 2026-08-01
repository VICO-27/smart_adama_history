<?php

namespace App\Services\AI\Contracts;

/**
 * Provider-agnostic LLM gateway contract (Req 5.2, 10.1).
 * Swap Claude ↔ any other provider by changing AI_LLM_PROVIDER in .env.
 */
interface LLMGatewayInterface
{
    /**
     * Stream a chat completion, yielding text token deltas one at a time.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options   provider-specific overrides (max_tokens, etc.)
     * @return \Generator<int, string, mixed, void>  yields string token deltas
     * @throws \App\Exceptions\AiProviderException on permanent failure
     */
    public function streamChat(array $messages, array $options = []): \Generator;

    /**
     * Non-streaming completion — returns the full response string.
     * Used by tests and one-shot utility calls.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @throws \App\Exceptions\AiProviderException on permanent failure
     */
    public function chat(array $messages, array $options = []): string;
}
