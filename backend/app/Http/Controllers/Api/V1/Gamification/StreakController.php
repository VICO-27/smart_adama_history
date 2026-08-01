<?php

namespace App\Http\Controllers\Api\V1\Gamification;

use App\Http\Controllers\Controller;
use App\Services\Gamification\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Learner streak info (Req 11.3)
 */
class StreakController extends Controller
{
    public function __construct(private readonly StreakService $streakService)
    {
    }

    /**
     * GET /users/me/streak
     * Return the current and longest streak for the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $streak = $this->streakService->getStreak($request->user());

        return response()->json([
            'streak' => [
                'current_streak'     => $streak->current_streak,
                'longest_streak'     => $streak->longest_streak,
                'last_activity_date' => $streak->last_activity_date?->toDateString(),
            ],
        ]);
    }
}
