<?php

use App\Models\Badge;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use App\Models\UserStreak;
use Illuminate\Support\Carbon;

// ── GET /api/v1/users/me/badges ───────────────────────────────────────────────

it('returns all badges with earned status for authenticated user', function () {
    $user       = User::factory()->create();
    $earnedBadge  = Badge::factory()->create(['criteria' => ['type' => 'chapter_count', 'threshold' => 1]]);
    $lockedBadge  = Badge::factory()->create(['criteria' => ['type' => 'chapter_count', 'threshold' => 10]]);

    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $earnedBadge->id,
        'awarded_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/users/me/badges')
        ->assertOk()
        ->assertJsonStructure(['badges' => [['id', 'code', 'name', 'description', 'icon', 'earned', 'awarded_at', 'progress']]]);

    $badges = collect($response->json('badges'));

    $earned = $badges->firstWhere('id', $earnedBadge->id);
    expect($earned['earned'])->toBeTrue();
    expect($earned['awarded_at'])->not->toBeNull();
    expect($earned['progress'])->toBeNull();

    $locked = $badges->firstWhere('id', $lockedBadge->id);
    expect($locked['earned'])->toBeFalse();
    expect($locked['awarded_at'])->toBeNull();
});

it('returns progress hints for unearned chapter_count badges', function () {
    $user  = User::factory()->create();
    $badge = Badge::factory()->create(['criteria' => ['type' => 'chapter_count', 'threshold' => 5]]);

    // User has completed 2 chapters
    Chapter::factory()->count(2)->create()->each(fn ($ch) =>
        UserProgress::factory()->create([
            'user_id'      => $user->id,
            'chapter_id'   => $ch->id,
            'is_completed' => true,
        ])
    );

    $response = $this->actingAs($user)->getJson('/api/v1/users/me/badges')->assertOk();

    $badgeData = collect($response->json('badges'))->firstWhere('id', $badge->id);
    expect($badgeData['progress']['current'])->toBe(2);
    expect($badgeData['progress']['required'])->toBe(5);
});

it('returns 401 for unauthenticated badge request', function () {
    $this->getJson('/api/v1/users/me/badges')->assertStatus(401);
});

// ── GET /api/v1/users/me/streak ───────────────────────────────────────────────

it('returns streak data for authenticated user', function () {
    $user = User::factory()->create();

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 5,
        'longest_streak'     => 12,
        'last_activity_date' => Carbon::today()->toDateString(),
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/users/me/streak')
        ->assertOk()
        ->assertJsonPath('streak.current_streak', 5)
        ->assertJsonPath('streak.longest_streak', 12)
        ->assertJsonPath('streak.last_activity_date', Carbon::today()->toDateString());
});

it('returns zeroed streak for a user with no activity', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/users/me/streak')
        ->assertOk()
        ->assertJsonPath('streak.current_streak', 0)
        ->assertJsonPath('streak.longest_streak', 0)
        ->assertJsonPath('streak.last_activity_date', null);
});

it('returns 401 for unauthenticated streak request', function () {
    $this->getJson('/api/v1/users/me/streak')->assertStatus(401);
});

// ── GET /api/v1/users/me/progress ─────────────────────────────────────────────

it('returns progress summary for authenticated user', function () {
    $user     = User::factory()->create();
    $chapters = Chapter::factory()->count(4)->create();

    // Complete 2 of the 4 chapters
    foreach ($chapters->take(2) as $ch) {
        UserProgress::factory()->create([
            'user_id'            => $user->id,
            'chapter_id'         => $ch->id,
            'is_completed'       => true,
            'best_quiz_score_pct' => 80,
        ]);
    }

    $this->actingAs($user)
        ->getJson('/api/v1/users/me/progress')
        ->assertOk()
        ->assertJsonStructure(['progress' => [
            'total_chapters',
            'completed_chapters',
            'completion_pct',
            'average_quiz_score',
        ]])
        ->assertJsonPath('progress.completed_chapters', 2)
        ->assertJsonPath('progress.completion_pct', 50);
});

it('returns 0 completion for a new user with no progress', function () {
    $user = User::factory()->create();
    Chapter::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/users/me/progress')
        ->assertOk()
        ->assertJsonPath('progress.completed_chapters', 0)
        ->assertJsonPath('progress.completion_pct', 0);
});

it('returns 401 for unauthenticated progress request', function () {
    $this->getJson('/api/v1/users/me/progress')->assertStatus(401);
});

// ── EvaluateBadgesJob integration (dispatched from QuizAttemptController) ─────

it('badge is awarded after passing a quiz via the full HTTP submit flow', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $user    = User::factory()->create();
    $chapter = Chapter::factory()->create();
    $badge   = Badge::factory()->create(['criteria' => ['type' => 'chapter_count', 'threshold' => 1]]);

    $quiz     = \App\Models\Quiz::factory()->published()->create(['chapter_id' => $chapter->id, 'passing_score_pct' => 70]);
    $question = \App\Models\QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'type' => 'single']);
    $correct  = \App\Models\QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    \App\Models\QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);

    $attempt = \App\Models\QuizAttempt::factory()->create([
        'user_id'    => $user->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $question->id, 'selected_option_ids' => [$correct->id]],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('badge_evaluation_triggered', true);

    // Job was queued
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\EvaluateBadgesJob::class);

    // Now run the job synchronously to verify the badge is awarded
    $service = new \App\Services\Gamification\BadgeEvaluationService();
    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->toContain($badge->id);
    $this->assertDatabaseHas('user_badges', ['user_id' => $user->id, 'badge_id' => $badge->id]);
});

// ── Req 11.2 — newly_earned flag in badge list ────────────────────────────────

it('badge awarded_at is present immediately after being earned', function () {
    $user  = User::factory()->create();
    $badge = Badge::factory()->create(['criteria' => ['type' => 'chapter_count', 'threshold' => 1]]);

    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $badge->id,
        'awarded_at' => now(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/users/me/badges')->assertOk();

    $badgeData = collect($response->json('badges'))->firstWhere('id', $badge->id);
    expect($badgeData['awarded_at'])->not->toBeNull();
    expect($badgeData['earned'])->toBeTrue();
});
