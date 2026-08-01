<?php

namespace App\Http\Controllers\Api\V1\Gamification;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Learner badge listing (Req 11.4)
 * Returns all badges (earned + locked) with progress toward locked ones.
 */
class BadgeController extends Controller
{
    /**
     * GET /users/me/badges
     * List all badges. Each entry includes earned status, award timestamp,
     * and measurable progress toward unearned badges (Req 11.4).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load all badges and the user's earned set in two queries
        $allBadges   = Badge::all();
        $earnedByBadgeId = UserBadge::where('user_id', $user->id)
            ->get()
            ->keyBy('badge_id');

        // Pre-compute stats needed for progress hints (Req 11.4)
        $completedChapters = $user->progress()->where('is_completed', true)->count();
        $passedQuizCount   = $user->quizAttempts()
            ->where('passed', true)
            ->whereNotNull('submitted_at')
            ->count();
        $currentStreak     = $user->streak?->current_streak ?? 0;
        $totalChapters     = \App\Models\Chapter::count();

        $badges = $allBadges->map(function (Badge $badge) use (
            $earnedByBadgeId,
            $completedChapters,
            $passedQuizCount,
            $currentStreak,
            $totalChapters,
        ) {
            $earned    = $earnedByBadgeId->has($badge->id);
            $userBadge = $earnedByBadgeId->get($badge->id);

            return [
                'id'          => $badge->id,
                'code'        => $badge->code,
                'name'        => $badge->name,
                'description' => $badge->description,
                'icon'        => $badge->icon,
                'earned'      => $earned,
                'awarded_at'  => $earned ? $userBadge->awarded_at : null,
                'progress'    => $earned
                    ? null
                    : $this->buildProgress($badge, $completedChapters, $passedQuizCount, $currentStreak, $totalChapters),
            ];
        });

        return response()->json(['badges' => $badges]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a progress hint object for an unearned badge (Req 11.4).
     * Returns ['current' => N, 'required' => N] or null for binary criteria.
     */
    private function buildProgress(
        Badge $badge,
        int $completedChapters,
        int $passedQuizCount,
        int $currentStreak,
        int $totalChapters,
    ): ?array {
        $criteria  = $badge->criteria;
        $type      = $criteria['type'] ?? null;
        $threshold = (int) ($criteria['threshold'] ?? 0);

        return match ($type) {
            'chapter_count'     => ['current' => $completedChapters, 'required' => $threshold],
            'quiz_passed_count' => ['current' => $passedQuizCount,   'required' => $threshold],
            'streak_days'       => ['current' => $currentStreak,     'required' => $threshold],
            'book_complete'     => ['current' => $completedChapters, 'required' => $totalChapters],
            'perfect_score'     => null, // binary — either you have it or you don't
            default             => null,
        };
    }
}
