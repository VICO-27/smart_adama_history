<?php

use App\Models\ChatSession;
use App\Models\User;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\RAG\RetrievalService;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Helper: bind a fake LLM gateway that returns a predictable response.
 */
function bindFakeLLM(string $response = 'Smart Adama teaches wisdom.'): void
{
    app()->bind(LLMGatewayInterface::class, function () use ($response) {
        return new class($response) implements LLMGatewayInterface {
            public function __construct(private string $resp) {}

            public function streamChat(array $messages, array $options = []): \Generator
            {
                foreach (str_split($this->resp, 5) as $chunk) {
                    yield $chunk;
                }
            }

            public function chat(array $messages, array $options = []): string
            {
                return $this->resp;
            }
        };
    });
}

/**
 * Helper: bind a fake embedding provider (no live API calls).
 */
function bindFakeEmbedder(): void
{
    app()->bind(EmbeddingProviderInterface::class, function () {
        return new class implements EmbeddingProviderInterface {
            public function embed(string $text): array { return array_fill(0, 1024, 0.1); }
            public function embedBatch(array $texts): array {
                return array_map(fn () => array_fill(0, 1024, 0.1), $texts);
            }
            public function getDimension(): int { return 1024; }
        };
    });
}

beforeEach(function () {
    config(['ai.rag.top_k' => 5, 'ai.rag.similarity_threshold' => 0.75]);
    bindFakeLLM();
    bindFakeEmbedder();
});

// ── POST /chat/sessions/{session}/messages ────────────────────────────────────

it('persists the user message and streams an assistant response', function () {
    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", [
            'content' => 'What is Smart Adama?',
        ]);

    // SSE responses return 200 with text/event-stream
    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');

    // Both user and assistant messages are persisted
    $this->assertDatabaseHas('chat_messages', [
        'chat_session_id' => $session->id,
        'role'            => 'user',
        'content'         => 'What is Smart Adama?',
    ]);

    $this->assertDatabaseHas('chat_messages', [
        'chat_session_id' => $session->id,
        'role'            => 'assistant',
    ]);
});

it('auto-titles the session from the first message', function () {
    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id, 'title' => 'New Chat']);

    $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", [
            'content' => 'Tell me about leadership in Smart Adama',
        ]);

    expect($session->fresh()->title)->not->toBe('New Chat');
});

it('updates last_activity_at on message send', function () {
    $user    = User::factory()->create();
    $session = ChatSession::factory()->create([
        'user_id'          => $user->id,
        'last_activity_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", [
            'content' => 'Hello',
        ]);

    expect($session->fresh()->last_activity_at->isToday())->toBeTrue();
});

it('rejects messages to another user session with 403', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", [
            'content' => 'Hi',
        ])
        ->assertStatus(403);
});

it('rejects unauthenticated message requests with 401', function () {
    $session = ChatSession::factory()->create();

    $this->postJson("/api/v1/chat/sessions/{$session->id}/messages", [
        'content' => 'Hello',
    ])->assertStatus(401);
});

it('rejects empty message content with 422', function () {
    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", ['content' => ''])
        ->assertStatus(422);
});

// ── Rate limiting (Req 16.2) ──────────────────────────────────────────────────

it('rate limits chat messages after exceeding the threshold', function () {
    config(['ai.chat_rate_limit.max_attempts' => 3, 'ai.chat_rate_limit.decay_minutes' => 5]);

    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    // Exhaust the limit
    foreach (range(1, 3) as $_) {
        $this->actingAs($user)
            ->postJson("/api/v1/chat/sessions/{$session->id}/messages", ['content' => 'Test']);
    }

    // 4th attempt should be blocked
    $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", ['content' => 'Blocked'])
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
});

it('rate limit response includes Retry-After header', function () {
    config(['ai.chat_rate_limit.max_attempts' => 1, 'ai.chat_rate_limit.decay_minutes' => 5]);

    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", ['content' => 'First']);

    $response = $this->actingAs($user)
        ->postJson("/api/v1/chat/sessions/{$session->id}/messages", ['content' => 'Blocked']);

    $response->assertStatus(429);
    expect($response->headers->has('Retry-After'))->toBeTrue();
});
