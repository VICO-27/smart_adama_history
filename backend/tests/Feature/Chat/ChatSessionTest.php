<?php

use App\Models\ChatSession;
use App\Models\User;

// ── POST /api/v1/chat/sessions ───────────────────────────────────────────────

it('creates a new chat session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/chat/sessions')
        ->assertStatus(201)
        ->assertJsonPath('session.title', 'New Chat')
        ->assertJsonStructure(['session' => ['id', 'title', 'last_activity_at', 'created_at']]);

    $this->assertDatabaseHas('chat_sessions', ['user_id' => $user->id]);
});

it('creates a session with a custom title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/chat/sessions', ['title' => 'My Study Session'])
        ->assertStatus(201)
        ->assertJsonPath('session.title', 'My Study Session');
});

// ── GET /api/v1/chat/sessions ─────────────────────────────────────────────────

it('lists sessions ordered by most recent activity', function () {
    $user = User::factory()->create();

    ChatSession::factory()->create([
        'user_id'          => $user->id,
        'title'            => 'Older',
        'last_activity_at' => now()->subHour(),
    ]);
    ChatSession::factory()->create([
        'user_id'          => $user->id,
        'title'            => 'Newer',
        'last_activity_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/chat/sessions')
        ->assertOk();

    expect($response->json('sessions.0.title'))->toBe('Newer');
});

it('does not list sessions belonging to other users', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    ChatSession::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/chat/sessions')
        ->assertOk()
        ->assertJsonCount(0, 'sessions');
});

// ── GET /api/v1/chat/sessions/{session} ──────────────────────────────────────

it('returns a session with its message history', function () {
    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);
    $session->messages()->create(['role' => 'user',      'content' => 'Hello']);
    $session->messages()->create(['role' => 'assistant', 'content' => 'Hi there!']);

    $this->actingAs($user)
        ->getJson("/api/v1/chat/sessions/{$session->id}")
        ->assertOk()
        ->assertJsonCount(2, 'session.messages');
});

it('returns 403 when accessing another user session', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->getJson("/api/v1/chat/sessions/{$session->id}")
        ->assertStatus(403);
});

// ── PATCH /api/v1/chat/sessions/{session} ────────────────────────────────────

it('renames a session', function () {
    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id, 'title' => 'Old']);

    $this->actingAs($user)
        ->patchJson("/api/v1/chat/sessions/{$session->id}", ['title' => 'New Title'])
        ->assertOk()
        ->assertJsonPath('session.title', 'New Title');
});

// ── DELETE /api/v1/chat/sessions/{session} ───────────────────────────────────

it('soft-deletes a session', function () {
    $user    = User::factory()->create();
    $session = ChatSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/chat/sessions/{$session->id}")
        ->assertStatus(204);

    $this->assertSoftDeleted('chat_sessions', ['id' => $session->id]);

    // Soft-deleted session no longer appears in list
    $this->actingAs($user)
        ->getJson('/api/v1/chat/sessions')
        ->assertJsonCount(0, 'sessions');
});

// ── Auth guards ───────────────────────────────────────────────────────────────

it('returns 401 without authentication', function () {
    $this->getJson('/api/v1/chat/sessions')->assertStatus(401);
    $this->postJson('/api/v1/chat/sessions')->assertStatus(401);
});
