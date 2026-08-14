<?php

use App\Models\User;

// ── EnsureAdmin middleware ───────────────────────────────────────────────────

it('allows admin users to reach admin routes', function () {
    $admin = User::factory()->admin()->create();

    // GET /api/v1/admin/analytics requires admin — just check it passes the middleware
    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics')
        ->assertOk()  // Controller is now implemented, returns 200
        ->assertJsonStructure(['analytics' => [
            'total_users',
            'total_chapters',
            'avg_completion_pct',
            'total_quiz_attempts',
            'total_quizzes_passed',
            'avg_quiz_score',
            'total_badges_awarded',
        ]]);
});

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
