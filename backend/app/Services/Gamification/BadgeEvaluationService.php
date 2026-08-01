<?php

namespace App\Services\Gamification;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Evaluates all badge criteria for a given user and awards any newly earned badges.
 * Requirements 11.1, 11.2, 11.4
 *
 * Criteria types (defined in BadgeSeeder):
 *   chapter_count      — N or more chapters completed
 *   perfect_score      — at least one quiz attempt with score_pct >= threshold
 *   streak_days        — current_streak >= threshold
 *   book_complete      — all chapters completed
 *   quiz_passed_count  — N or more passed quiz attempts
 */
class BadgeEvaluationService
{
    /**
     * Evaluate every badge for the given user.
     * Returns the list of newly awarded Badge models (empty if none new).
     *
     * @return Collection<int, Badge>
     */
    public function evaluate(User $user): Collection
    {
        $alreadyEarned = UserBadge::where('user_id', $user->id)
            ->pluck('badge_id')
            ->flip(); // flip to use as a set for O(1) lookup

        $allBadges = Badge::all();
        $newlyAwarded = collect();

        // Pre-load stats once to avoid N+1 queries inside the loop
        $stats = $this->gatherStats($user);

        foreach ($allBadges as $badge) {
            // Skip badges the user already has
            if ($alreadyEarned->has($badge->id)) {
                continue;
            }

            if ($this->meetsCriteria($badge, $stats)) {
                DB::transaction(function () use ($user, $badge, $newlyAwarded) {
                    // Guard against race conditions with insertOrIgnore
                    $inserted = DB::table('user_badges')->insertOrIgnore([
                        'id'         => (string) \Illuminate\Support\Str::uuid(),
                        'user_id'    => $user->id,
                        'badge_id'   => $badge->id,
                        'awarded_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($inserted) {
                        $newlyAwarded->push($badge);

                        Log::info('Badge awarded', [
                            'user_id'  => $user->id,
                            'badge'    => $badge->code,
                        ]);
                    }
                });
            }
        }

        return $newlyAwarded;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Gather all stats needed for criteria evaluation in one pass.
     */
    private function gatherStats(User $user): array
    {
        $progress = UserProgress::where('user_id', $user->id)->get();

        $completedChapters = $progress->where('is_completed', true)->count();
        $totalChapters     = \App\Models\Chapter::count();

        $passedAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('passed', true)
            ->whereNotNull('submitted_at')
            ->get();

        $hasPerfectScore = $passedAttempts->contains(
            fn ($a) => $a->score_pct >= 100
        );

        $streak = $user->streak;

        return [
            'completed_chapters' => $completedChapters,
            'total_chapters'     => $totalChapters,
            'passed_quiz_count'  => $passedAttempts->count(),
            'has_perfect_score'  => $hasPerfectScore,
            'current_streak'     => $streak?->current_streak ?? 0,
            'book_complete'      => $totalChapters > 0 && $completedChapters >= $totalChapters,
        ];
    }

    /**
     * Test a single badge's criteria against the pre-gathered stats.
     */
    private function meetsCriteria(Badge $badge, array $stats): bool
    {
        $criteria  = $badge->criteria;
        $type      = $criteria['type'] ?? null;
        $threshold = (int) ($criteria['threshold'] ?? 0);

        return match ($type) {
            'chapter_count'     => $stats['completed_chapters'] >= $threshold,
            'perfect_score'     => $stats['has_perfect_score'],
            'streak_days'       => $stats['current_streak'] >= $threshold,
            'book_complete'     => $stats['book_complete'],
            'quiz_passed_count' => $stats['passed_quiz_count'] >= $threshold,
            default             => false,
        };
    }
}
