<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Services\Progress\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Learner progress summary (Req 10.2)
 */
class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progressService)
    {
    }

    /**
     * GET /users/me/progress
     * Return overall completion %, chapters completed, and average quiz score (Req 10.2).
     * Result is cached for 60 s and invalidated on any progress-affecting write (Req 12.3).
     */
    public function show(Request $request): JsonResponse
    {
        $summary = $this->progressService->getSummary($request->user());

        return response()->json(['progress' => $summary]);
    }
}
