<?php

namespace App\Services\Gamification;

use App\Models\User;
use App\Models\UserStreak;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Manages daily learning streaks per user (Req 11.3).
 *
 * Rules:
 *   - Activity on the same calendar day as last_activity_date: no-op (already counted).
 *   - Activity on the calendar day immediately after last_activity_date: increment streak.
 *   - Activity after a gap of 2+ days: reset streak to 1.
 *   - longest_streak is updated whenever current_streak surpasses it.
 */
class StreakService
{
    /**
     * Record that the given user was active today.
     * Safe to call multiple times per day — idempotent within the same calendar day.
     */
    public function recordActivity(User $user): UserStreak
    {
        $streak = UserStreak::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak'     => 0,
                'longest_streak'     => 0,
                'last_activity_date' => null,
            ]
        );

        $today = Carbon::today()->toDateString();
        $last  = $streak->last_activity_date?->toDateString();

        // Same day — already recorded, nothing to change
        if ($last === $today) {
            return $streak;
        }

        $yesterday = Carbon::yesterday()->toDateString();

        if ($last === $yesterday) {
            // Consecutive day — extend the streak
            $newCurrent = $streak->current_streak + 1;
        } else {
            // Gap of 2+ days (or first ever activity) — reset
            $newCurrent = 1;
        }

        $newLongest = max($newCurrent, $streak->longest_streak);

        $streak->update([
            'current_streak'     => $newCurrent,
            'longest_streak'     => $newLongest,
            'last_activity_date' => $today,
        ]);

        Log::info('StreakService: activity recorded', [
            'user_id'        => $user->id,
            'current_streak' => $newCurrent,
            'longest_streak' => $newLongest,
        ]);

        return $streak->fresh();
    }

    /**
     * Return the streak record for a user (creates a zeroed record if none exists).
     */
    public function getStreak(User $user): UserStreak
    {
        return UserStreak::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak'     => 0,
                'longest_streak'     => 0,
                'last_activity_date' => null,
            ]
        );
    }
}
