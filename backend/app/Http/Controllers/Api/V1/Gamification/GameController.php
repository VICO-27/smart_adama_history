<?php

namespace App\Http\Controllers\Api\V1\Gamification;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Get global leaderboard ranked by total XP and progress.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        // Calculate XP dynamically: (completed_chapters * 150) + (quizzes_passed * 100) + (current_streak * 20)
        $users = User::with(['streak', 'progress', 'badges'])
            ->get()
            ->map(function ($user) {
                $completed = $user->progress->where('is_completed', true)->count();
                $quizzesPassed = $user->quizAttempts()->where('passed', true)->count();
                $streak = $user->streak?->current_streak ?? 0;
                
                $xp = ($completed * 150) + ($quizzesPassed * 100) + ($streak * 20);
                $level = floor($xp / 1000) + 1;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'xp' => $xp,
                    'level' => (int) $level,
                    'streak' => $streak,
                ];
            })
            ->sortByDesc('xp')
            ->values();

        return response()->json([
            'leaderboard' => $users,
            'current_user_id' => $request->user()->id,
        ]);
    }

    /**
     * Get today's daily challenge status and question.
     */
    public function dailyChallenge(Request $request): JsonResponse
    {
        // Provide a structured daily challenge based on Smart Adama core pillars
        $challenge = [
            'id' => 'daily-' . date('Y-m-d'),
            'question' => 'Which core pillar focuses on supporting startups and local digital economic growth?',
            'options' => ['e-Governance', 'Enterprise', 'Innovation'],
            'correct_index' => 1,
            'xp_reward' => 150,
            'completed' => false, // Can be extended with a daily tracking table if needed
        ];

        return response()->json(['daily_challenge' => $challenge]);
    }
}