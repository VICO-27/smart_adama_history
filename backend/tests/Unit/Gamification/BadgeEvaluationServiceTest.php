<?php

use App\Models\Badge;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use App\Models\UserStreak;
use App\Services\Gamification\BadgeEvaluationService;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeBadge(string $type, int $threshold, string $code = null): Badge
{
    return Badge::factory()->create([
        'code'     => $code ?? "{$type}_{$threshold}_" . uniqid(),
        'criteria' => ['type' => $type, 'threshold' => $threshold],
    ]);
}

function completeChapters(User $user, int $count): void
{
    Chapter::factory()->count($count)->create()->each(function ($chapter) use ($user) {
        UserProgress::factory()->create([
            'user_id'      => $user->id,
            'chapter_id'   => $chapter->id,
            'is_completed' => true,
        ]);
    });
}

// ── chapter_count badges ──────────────────────────────────────────────────────

it('awards a chapter_count badge when threshold is met', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('chapter_count', 1);

    completeChapters($user, 1);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->toContain($badge->id);
    $this->assertDatabaseHas('user_badges', ['user_id' => $user->id, 'badge_id' => $badge->id]);
});

it('does not award a chapter_count badge when threshold is not yet met', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('chapter_count', 5);

    completeChapters($user, 3);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->not->toContain($badge->id);
    $this->assertDatabaseMissing('user_badges', ['user_id' => $user->id, 'badge_id' => $badge->id]);
});

it('does not re-award a badge the user already has', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('chapter_count', 1);

    completeChapters($user, 1);

    // Pre-seed the badge as already earned
    UserBadge::create([
        'user_id'    => $user->id,
        'badge_id'   => $badge->id,
        'awarded_at' => now()->subDay(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->not->toContain($badge->id);
    // Still only one row in user_badges
    expect(UserBadge::where('user_id', $user->id)->where('badge_id', $badge->id)->count())->toBe(1);
});

// ── perfect_score badges ──────────────────────────────────────────────────────

it('awards a perfect_score badge when a 100% attempt exists', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('perfect_score', 100);

    QuizAttempt::factory()->create([
        'user_id'      => $user->id,
        'score_pct'    => 100,
        'passed'       => true,
        'submitted_at' => now(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->toContain($badge->id);
});

it('does not award perfect_score badge for a non-100 score', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('perfect_score', 100);

    QuizAttempt::factory()->create([
        'user_id'      => $user->id,
        'score_pct'    => 80,
        'passed'       => true,
        'submitted_at' => now(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->not->toContain($badge->id);
});

// ── streak_days badges ────────────────────────────────────────────────────────

it('awards a streak_days badge when current_streak meets the threshold', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('streak_days', 3);

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 3,
        'longest_streak'     => 3,
        'last_activity_date' => now()->toDateString(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->toContain($badge->id);
});

it('does not award a streak_days badge when streak is below threshold', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('streak_days', 7);

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 5,
        'longest_streak'     => 5,
        'last_activity_date' => now()->toDateString(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->not->toContain($badge->id);
});

// ── book_complete badge ───────────────────────────────────────────────────────

it('awards book_complete badge when all chapters are completed', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('book_complete', 1);

    // Create exactly 2 chapters and complete both
    completeChapters($user, 2);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->toContain($badge->id);
});

it('does not award book_complete badge when only some chapters are done', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('book_complete', 1);

    // Create 2 chapters but only complete 1
    $chapters = Chapter::factory()->count(2)->create();
    UserProgress::factory()->create([
        'user_id'      => $user->id,
        'chapter_id'   => $chapters[0]->id,
        'is_completed' => true,
    ]);
    UserProgress::factory()->create([
        'user_id'      => $user->id,
        'chapter_id'   => $chapters[1]->id,
        'is_completed' => false,
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->not->toContain($badge->id);
});

// ── quiz_passed_count badges ──────────────────────────────────────────────────

it('awards quiz_passed_count badge when threshold is reached', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('quiz_passed_count', 3);

    QuizAttempt::factory()->count(3)->create([
        'user_id'      => $user->id,
        'passed'       => true,
        'submitted_at' => now(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->toContain($badge->id);
});

it('does not award quiz_passed_count badge when count is below threshold', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    $badge   = makeBadge('quiz_passed_count', 10);

    QuizAttempt::factory()->count(5)->create([
        'user_id'      => $user->id,
        'passed'       => true,
        'submitted_at' => now(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->not->toContain($badge->id);
});

// ── Multiple badges in one evaluation pass ────────────────────────────────────

it('awards multiple badges in a single evaluate() call', function () {
    $service  = new BadgeEvaluationService();
    $user     = User::factory()->create();
    $badge1   = makeBadge('chapter_count', 1);
    $badge2   = makeBadge('perfect_score', 100);

    completeChapters($user, 1);
    QuizAttempt::factory()->create([
        'user_id'      => $user->id,
        'score_pct'    => 100,
        'passed'       => true,
        'submitted_at' => now(),
    ]);

    $awarded = $service->evaluate($user);

    expect($awarded->pluck('id'))->toContain($badge1->id);
    expect($awarded->pluck('id'))->toContain($badge2->id);
});

// ── Unknown criteria type is ignored safely ───────────────────────────────────

it('ignores badges with unknown criteria types without throwing', function () {
    $service = new BadgeEvaluationService();
    $user    = User::factory()->create();
    Badge::factory()->create(['criteria' => ['type' => 'unknown_future_type', 'threshold' => 1]]);

    // Should not throw
    $awarded = $service->evaluate($user);
    expect($awarded)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
