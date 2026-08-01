<?php

namespace App\Http\Controllers\Api\V1\Quizzes;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Learner quiz access (Req 9.1)
 */
class QuizController extends Controller
{
    /**
     * GET /chapters/{chapter}/quiz
     * Return the published quiz for a chapter WITHOUT exposing correct answers (Req 9.1).
     * Returns an empty-state 200 when no quiz exists yet (Req 3.7).
     */
    public function showByChapter(Request $request, Chapter $chapter): JsonResponse
    {
        $quiz = $chapter->quiz()
            ->where('status', 'published')
            ->with('questions.options')
            ->first();

        if (! $quiz) {
            return response()->json(['quiz' => null]);
        }

        // Look up whether the authenticated user has a prior attempt on this quiz
        $bestAttempt = $quiz->attempts()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('score_pct')
            ->first();

        return response()->json([
            'quiz'         => new QuizResource($quiz, includeAnswers: false),
            'best_attempt' => $bestAttempt ? [
                'id'        => $bestAttempt->id,
                'score_pct' => $bestAttempt->score_pct,
                'passed'    => $bestAttempt->passed,
                'submitted_at' => $bestAttempt->submitted_at,
            ] : null,
        ]);
    }
}
