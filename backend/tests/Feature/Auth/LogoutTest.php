<?php

use App\Models\User;

// ── POST /api/v1/auth/logout ─────────────────────────────────────────────────

it('logs out and revokes the current token', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');

    // Token row must be gone from the database
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id'   => $user->id,
        'tokenable_type' => get_class($user),
    ]);
});

it('rejects logout without a token', function () {
    $this->postJson('/api/v1/auth/logout')
        ->assertStatus(401);
});
