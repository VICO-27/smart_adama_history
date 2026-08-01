<?php

use App\Models\Badge;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use App\Models\UserStreak;
use Illuminate\Support\Facades\Cache;

// ── GET /api/v1/dashboard ─────────────────────────────────────────────────────

it('returns a full dashboard payload for authenticated user', function () {
    $user     = User::factory()->create();
    $chapters = Chapter::factory()->count(4)->create();

    UserProgress::factory()->create([
        'user_id'    => $user->id,
        'chapter_id' => $chapters[0]->id,
        'is_completed' => true,
    ]);

    QuizAttempt::factory()->create([
        'user_id'      => $user->id,
        'passed'       => true,
        'score_pct'    => 80,
        'submitted_at' => now(),
    ]);

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 3,
        'longest_streak'     => 3,
        'last_activity_date' => now()->toDateString(),
    ]);

    $badge = Badge::factory()->create();
    UserBadge::create(['user_id' => $user->id, 'badge_id' => $badge->id, 'awarded_at' => now()]);

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonStructure(['dashboard' => [
            'completion_pct',
            'total_chapters',
            'completed_chapters',
            'quizzes_passed',
            'average_quiz_score',
            'current_streak',
            'total_chat_sessions',
            'earned_badge_count',
        ]])
        ->assertJsonPath('dashboard.completed_chapters', 1)
        ->assertJsonPath('dashboard.quizzes_passed', 1)
        ->assertJsonPath('dashboard.current_streak', 3)
        ->assertJsonPath('dashboard.earned_badge_count', 1);
});

it('returns zeroed dashboard for a brand new user', function () {
    $user = User::factory()->create();
    Chapter::factory()->count(2)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('dashboard.completed_chapters', 0)
        ->assertJsonPath('dashboard.quizzes_passed', 0)
        ->assertJsonPath('dashboard.current_streak', 0)
        ->assertJsonPath('dashboard.earned_badge_count', 0)
        ->assertJsonPath('dashboard.average_quiz_score', null);
});

it('returns 401 for unauthenticated dashboard request', function () {
    $this->getJson('/api/v1/dashboard')->assertStatus(401);
});

it('dashboard response is served from cache on second call', function () {
    $user = User::factory()->create();

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'completion_pct'      => 0,
            'total_chapters'      => 0,
            'completed_chapters'  => 0,
            'quizzes_passed'      => 0,
            'average_quiz_score'  => null,
            'current_streak'      => 0,
            'total_chat_sessions' => 0,
            'earned_badge_count'  => 0,
        ]);

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk();
});

// ── GET /api/v1/admin/analytics ───────────────────────────────────────────────

it('admin can retrieve platform analytics', function () {
    $admin = User::factory()->admin()->create();
    Chapter::factory()->count(3)->create();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics')
        ->assertOk()
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

it('returns 403 when learner tries to access admin analytics', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->getJson('/api/v1/admin/analytics')
        ->assertStatus(403);
});

it('returns 401 for unauthenticated analytics request', function () {
    $this->getJson('/api/v1/admin/analytics')->assertStatus(401);
});

it('analytics total_users reflects only non-deleted users', function () {
    $admin   = User::factory()->admin()->create();
    $active  = User::factory()->count(3)->create();
    $deleted = User::factory()->create();
    $deleted->delete(); // soft delete

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics')
        ->assertOk();

    // admin + 3 active = 4 (soft-deleted excluded)
    expect($response->json('analytics.total_users'))->toBe(4);
});
