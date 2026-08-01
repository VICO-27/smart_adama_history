<?php

namespace App\Http\Controllers\Api\V1\Quizzes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quizzes\StoreQuizRequest;
use App\Http\Resources\QuizResource;
use App\Models\Chapter;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;

/**
 * Admin quiz management (Req 8.1, 8.3, 8.4)
 */
class AdminQuizController extends Controller
{
    /**
     * POST /admin/chapters/{chapter}/quizzes
     * Create a new quiz linked to a chapter (Req 8.1).
     * A chapter may only have one quiz.
     */
    public function store(StoreQuizRequest $request, Chapter $chapter): JsonResponse
    {
        if ($chapter->quiz()->exists()) {
            return response()->json([
                'message' => 'This chapter already has a quiz.',
            ], 422);
        }

        $quiz = $chapter->quiz()->create([
            'title'            => $request->title,
            'passing_score_pct' => $request->input('passing_score_pct', 70),
            'status'           => 'draft',
        ]);

        return response()->json([
            'quiz' => new QuizResource($quiz->load('questions.options'), includeAnswers: true),
        ], 201);
    }

    /**
     * POST /admin/quizzes/{quiz}/publish
     * Publish a quiz after validating all questions are complete (Req 8.3).
     */
    public function publish(Quiz $quiz): JsonResponse
    {
        if ($quiz->status === 'published') {
            return response()->json(['message' => 'Quiz is already published.'], 422);
        }

        $quiz->load('questions.options');

        if ($quiz->questions->isEmpty()) {
            return response()->json([
                'message' => 'A quiz must have at least one question before publishing.',
            ], 422);
        }

        // Validate every question has complete options with ≥1 correct answer (Req 8.3)
        foreach ($quiz->questions as $question) {
            if ($question->options->count() < 2) {
                return response()->json([
                    'message'     => "Question \"{$question->question_text}\" must have at least 2 options.",
                    'question_id' => $question->id,
                ], 422);
            }

            $hasCorrect = $question->options->contains('is_correct', true);

            if (! $hasCorrect) {
                return response()->json([
                    'message'     => "Question \"{$question->question_text}\" must have at least one correct option.",
                    'question_id' => $question->id,
                ], 422);
            }

            $hasEmpty = $question->options->contains(fn ($o) => trim($o->option_text) === '');

            if ($hasEmpty) {
                return response()->json([
                    'message'     => "Question \"{$question->question_text}\" has an option with empty text.",
                    'question_id' => $question->id,
                ], 422);
            }
        }

        $quiz->update(['status' => 'published']);

        return response()->json([
            'quiz' => new QuizResource($quiz->refresh()->load('questions.options'), includeAnswers: true),
        ]);
    }
}
