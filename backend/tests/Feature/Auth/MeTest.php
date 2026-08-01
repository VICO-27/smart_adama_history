<?php

use App\Models\User;

// ── GET /api/v1/auth/me ──────────────────────────────────────────────────────

it('returns the authenticated user', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.role', 'learner');
});

it('returns 401 when unauthenticated', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});
