<?php

use App\Services\AI\Contracts\LLMGatewayInterface;

/**
 * Tests that use a faked LLMGatewayInterface binding — no live API calls in CI.
 * The real ClaudeLLMGateway is tested via integration tests when ANTHROPIC_API_KEY is set.
 */

it('faked LLM gateway yields tokens and accumulates a full response', function () {
    // Bind a fake gateway in the container
    $fake = new class implements LLMGatewayInterface {
        public function streamChat(array $messages, array $options = []): \Generator
        {
            foreach (['Hello', ' ', 'world', '!'] as $token) {
                yield $token;
            }
        }

        public function chat(array $messages, array $options = []): string
        {
            $full = '';
            foreach ($this->streamChat($messages) as $token) {
                $full .= $token;
            }
            return $full;
        }
    };

    app()->instance(LLMGatewayInterface::class, $fake);

    $gateway = app(LLMGatewayInterface::class);
    $result  = $gateway->chat([['role' => 'user', 'content' => 'Hi']]);

    expect($result)->toBe('Hello world!');
});

it('faked LLM gateway can be used as a generator in a streaming loop', function () {
    $fake = new class implements LLMGatewayInterface {
        public function streamChat(array $messages, array $options = []): \Generator
        {
            yield 'Smart ';
            yield 'Adama ';
            yield 'is great.';
        }

        public function chat(array $messages, array $options = []): string
        {
            return 'Smart Adama is great.';
        }
    };

    app()->instance(LLMGatewayInterface::class, $fake);

    $gateway = app(LLMGatewayInterface::class);
    $tokens  = [];

    foreach ($gateway->streamChat([['role' => 'user', 'content' => 'Tell me']]) as $token) {
        $tokens[] = $token;
    }

    expect($tokens)->toBe(['Smart ', 'Adama ', 'is great.']);
});
