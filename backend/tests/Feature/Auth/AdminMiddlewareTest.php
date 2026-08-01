<?php

use App\Models\User;

// ── EnsureAdmin middleware ───────────────────────────────────────────────────

it('allows admin users to reach admin routes', function () {
    $admin = User::factory()->admin()->create();

    // GET /api/v1/admin/analytics requires admin — just check it passes the middleware
    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics')
        ->assertNotFound() // Controller not implemented yet but middleware passed
        ->assertStatus(fn ($s) => $s !== 403);
})->skip('Skipped until AdminAnalyticsController is implemented');

it('returns 403 for non-admin users on admin routes', function () {
    $learner = User::factory()->create(['role' => 'learner']);

    $this->actingAs($learner)
        ->postJson('/api/v1/admin/books', ['title' => 'Test'])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

it('returns 401 for unauthenticated requests to admin routes', function () {
    $this->postJson('/api/v1/admin/books', ['title' => 'Test'])
        ->assertStatus(401);
});
