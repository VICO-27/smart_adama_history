<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Learner dashboard — single cached payload (Req 12.1, 12.2, 12.3)
 * Cache TTL: 60 seconds, invalidated by ProgressService on any write.
 */
class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user     = $request->user();
        $cacheKey = "dashboard:{$user->id}";

        $data = Cache::remember($cacheKey, 60, function () use ($user) {
            $totalChapters     = Chapter::count();
            $progressRecords   = UserProgress::where('user_id', $user->id)->get();
            $completedChapters = $progressRecords->where('is_completed', true)->count();
            $completionPct     = $totalChapters > 0
                ? round(($completedChapters / $totalChapters) * 100, 1)
                : 0;

            $attempts       = QuizAttempt::where('user_id', $user->id)
                ->whereNotNull('submitted_at')
                ->get();
            $quizzesPassed  = $attempts->where('passed', true)->count();
            $avgQuizScore   = $attempts->isNotEmpty()
                ? round($attempts->avg('score_pct'), 1)
                : null;

            $streak        = $user->streak;
            $currentStreak = $streak?->current_streak ?? 0;

            $chatCount  = $user->chatSessions()->count();
            $badgeCount = $user->badges()->count();

            return [
                'completion_pct'     => $completionPct,
                'total_chapters'     => $totalChapters,
                'completed_chapters' => $completedChapters,
                'quizzes_passed'     => $quizzesPassed,
                'average_quiz_score' => $avgQuizScore,
                'current_streak'     => $currentStreak,
                'total_chat_sessions' => $chatCount,
                'earned_badge_count' => $badgeCount,
            ];
        });

        return response()->json(['dashboard' => $data]);
    }
}
