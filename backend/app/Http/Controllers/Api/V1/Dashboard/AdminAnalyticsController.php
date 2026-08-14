<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin aggregate platform analytics (Req 12.4 — P2)
 * Cache TTL: 60 seconds.
 */
class AdminAnalyticsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $data = Cache::remember('admin_analytics', 60, function () {
            $totalUsers        = User::whereNull('deleted_at')->count();
            $totalChapters     = Chapter::count();

            $completedProgress = UserProgress::where('is_completed', true)->count();
            $avgCompletion     = $totalUsers > 0 && $totalChapters > 0
                ? round(($completedProgress / ($totalUsers * $totalChapters)) * 100, 1)
                : 0;

            $attempts      = QuizAttempt::whereNotNull('submitted_at');
            $totalAttempts = (clone $attempts)->count();
            $passedAttempts = (clone $attempts)->where('passed', true)->count();
            $avgScore      = $totalAttempts > 0
                ? round((clone $attempts)->avg('score_pct'), 1)
                : null;

            $totalBadgesAwarded = UserBadge::count();

            return [
                'total_users'           => $totalUsers,
                'total_chapters'        => $totalChapters,
                'avg_completion_pct'    => $avgCompletion,
                'total_quiz_attempts'   => $totalAttempts,
                'total_quizzes_passed'  => $passedAttempts,
                'avg_quiz_score'        => $avgScore,
                'total_badges_awarded'  => $totalBadgesAwarded,
            ];
        });

        return response()->json(['analytics' => $data]);
    }
}
