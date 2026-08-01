<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

// ── POST /api/v1/auth/password/forgot ────────────────────────────────────────

it('responds identically whether the email exists or not (no enumeration)', function () {
    // Non-existent email
    $responseA = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'nobody@example.com',
    ]);

    // Existing email
    User::factory()->create(['email' => 'real@example.com']);
    $responseB = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'real@example.com',
    ]);

    $responseA->assertOk();
    $responseB->assertOk();
    expect($responseA->json('message'))->toBe($responseB->json('message'));
});

it('sends a reset notification for a known email', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'real@example.com']);

    $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'real@example.com',
    ])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

// ── POST /api/v1/auth/password/reset ─────────────────────────────────────────

it('rejects an invalid reset token', function () {
    User::factory()->create(['email' => 'real@example.com']);

    $this->postJson('/api/v1/auth/password/reset', [
        'token'    => 'bad-token',
        'email'    => 'real@example.com',
        'password' => 'newpass1',
    ])->assertStatus(422);
});
