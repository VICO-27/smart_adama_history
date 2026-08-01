<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an AI provider (LLM or embedding) call fails after all retries.
 * Mapped to HTTP 503 by Handler::renderApiException().
 * Never includes the raw provider error message in the HTTP response.
 */
class AiProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $provider,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
