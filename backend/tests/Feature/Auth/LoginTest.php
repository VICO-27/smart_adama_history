<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

// ── POST /api/v1/auth/login ──────────────────────────────────────────────────

it('logs in with valid credentials and returns a token', function () {
    $user = User::factory()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email'    => 'test@example.com',
        'password' => 'secret123',
    ])->assertOk()
        ->assertJsonStructure(['user', 'token']);
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email'    => 'test@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422);
});

it('throttles after 5 failed attempts', function () {
    User::factory()->create(['email' => 'target@example.com']);

    // Exhaust the 5-attempt limit
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'target@example.com',
            'password' => 'wrong',
        ]);
    }

    // The 6th attempt should be blocked
    $this->postJson('/api/v1/auth/login', [
        'email'    => 'target@example.com',
        'password' => 'wrong',
    ])->assertStatus(429);
});
