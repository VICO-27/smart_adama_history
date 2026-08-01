<?php

use App\Models\User;
use App\Models\UserStreak;
use App\Services\Gamification\StreakService;
use Illuminate\Support\Carbon;

// ── First activity ever ───────────────────────────────────────────────────────

it('creates a streak record with current_streak=1 on first activity', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    $streak = $service->recordActivity($user);

    expect($streak->current_streak)->toBe(1)
        ->and($streak->longest_streak)->toBe(1)
        ->and($streak->last_activity_date->toDateString())->toBe(Carbon::today()->toDateString());
});

// ── Same-day idempotency ──────────────────────────────────────────────────────

it('does not increment streak when called twice on the same day', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    $service->recordActivity($user);
    $streak = $service->recordActivity($user); // second call same day

    expect($streak->current_streak)->toBe(1);
});

// ── Consecutive day extends streak ───────────────────────────────────────────

it('increments streak when activity is on the day after last activity', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    // Seed a streak record as of yesterday
    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 2,
        'longest_streak'     => 2,
        'last_activity_date' => Carbon::yesterday()->toDateString(),
    ]);

    $streak = $service->recordActivity($user);

    expect($streak->current_streak)->toBe(3)
        ->and($streak->longest_streak)->toBe(3);
});

// ── Gap resets streak ─────────────────────────────────────────────────────────

it('resets current_streak to 1 when a day is missed', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 5,
        'longest_streak'     => 5,
        'last_activity_date' => Carbon::today()->subDays(2)->toDateString(),
    ]);

    $streak = $service->recordActivity($user);

    expect($streak->current_streak)->toBe(1);
});

it('preserves longest_streak when current is reset after a gap', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 10,
        'longest_streak'     => 10,
        'last_activity_date' => Carbon::today()->subDays(3)->toDateString(),
    ]);

    $streak = $service->recordActivity($user);

    expect($streak->current_streak)->toBe(1)
        ->and($streak->longest_streak)->toBe(10); // preserved
});

// ── longest_streak updated correctly ─────────────────────────────────────────

it('updates longest_streak when current_streak surpasses it', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 6,
        'longest_streak'     => 6,
        'last_activity_date' => Carbon::yesterday()->toDateString(),
    ]);

    $streak = $service->recordActivity($user);

    expect($streak->current_streak)->toBe(7)
        ->and($streak->longest_streak)->toBe(7);
});

it('does not decrease longest_streak when current is below it', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    // Previous best was 15; current is 4; after gap, reset to 1
    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 4,
        'longest_streak'     => 15,
        'last_activity_date' => Carbon::today()->subDays(5)->toDateString(),
    ]);

    $streak = $service->recordActivity($user);

    expect($streak->current_streak)->toBe(1)
        ->and($streak->longest_streak)->toBe(15);
});

// ── getStreak returns zeroed record for new user ──────────────────────────────

it('getStreak creates a zero record for a user with no prior activity', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    $streak = $service->getStreak($user);

    expect($streak->current_streak)->toBe(0)
        ->and($streak->longest_streak)->toBe(0)
        ->and($streak->last_activity_date)->toBeNull();
});

it('getStreak returns existing record without modifying it', function () {
    $service = new StreakService();
    $user    = User::factory()->create();

    UserStreak::create([
        'user_id'            => $user->id,
        'current_streak'     => 7,
        'longest_streak'     => 12,
        'last_activity_date' => Carbon::yesterday()->toDateString(),
    ]);

    $streak = $service->getStreak($user);

    expect($streak->current_streak)->toBe(7)
        ->and($streak->longest_streak)->toBe(12);
});
