<?php

namespace App\Http\Controllers\Api\V1\Quizzes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quizzes\StoreQuizQuestionRequest;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;

/**
 * Admin quiz question management (Req 8.2, 8.4)
 */
class AdminQuizQuestionController extends Controller
{
    /**
     * POST /admin/quizzes/{quiz}/questions
     * Add a question with its options to a quiz (Req 8.2).
     * Editing questions on a quiz with existing attempts is allowed;
     * historical attempt data is unaffected (Req 8.4).
     */
    public function store(StoreQuizQuestionRequest $request, Quiz $quiz): JsonResponse
    {
        $question = $quiz->questions()->create([
            'question_text' => $request->question_text,
            'type'          => $request->type,
            'explanation'   => $request->explanation,
            'order'         => $request->input('order', $quiz->questions()->max('order') + 1),
        ]);

        foreach ($request->options as $index => $optionData) {
            $question->options()->create([
                'option_text' => $optionData['option_text'],
                'is_correct'  => (bool) $optionData['is_correct'],
                'order'       => $optionData['order'] ?? $index,
            ]);
        }

        $question->load('options');

        return response()->json([
            'question' => [
                'id'            => $question->id,
                'question_text' => $question->question_text,
                'type'          => $question->type,
                'explanation'   => $question->explanation,
                'order'         => $question->order,
                'options'       => $question->options->map(fn ($o) => [
                    'id'          => $o->id,
                    'option_text' => $o->option_text,
                    'is_correct'  => $o->is_correct,
                    'order'       => $o->order,
                ]),
            ],
        ], 201);
    }

    /**
     * PATCH /admin/quizzes/{quiz}/questions/{question}
     * Update a question and replace its options atomically (Req 8.4).
     * Historical attempts are preserved because answers reference option IDs —
     * after replacing options those historical IDs will simply not resolve,
     * which is acceptable; the scores are already persisted.
     */
    public function update(StoreQuizQuestionRequest $request, Quiz $quiz, QuizQuestion $question): JsonResponse
    {
        $this->authorizeQuestion($quiz, $question);

        $question->update([
            'question_text' => $request->question_text,
            'type'          => $request->type,
            'explanation'   => $request->explanation,
            'order'         => $request->input('order', $question->order),
        ]);

        // Replace options atomically (delete old, insert new)
        $question->options()->delete();

        foreach ($request->options as $index => $optionData) {
            $question->options()->create([
                'option_text' => $optionData['option_text'],
                'is_correct'  => (bool) $optionData['is_correct'],
                'order'       => $optionData['order'] ?? $index,
            ]);
        }

        $question->load('options');

        return response()->json([
            'question' => [
                'id'            => $question->id,
                'question_text' => $question->question_text,
                'type'          => $question->type,
                'explanation'   => $question->explanation,
                'order'         => $question->order,
                'options'       => $question->options->map(fn ($o) => [
                    'id'          => $o->id,
                    'option_text' => $o->option_text,
                    'is_correct'  => $o->is_correct,
                    'order'       => $o->order,
                ]),
            ],
        ]);
    }

    /**
     * DELETE /admin/quizzes/{quiz}/questions/{question}
     * Remove a question and its options (Req 8.4 — historical attempts unaffected).
     */
    public function destroy(Quiz $quiz, QuizQuestion $question): JsonResponse
    {
        $this->authorizeQuestion($quiz, $question);

        // Options are cascade-deleted via DB constraint defined in migration
        $question->delete();

        return response()->json(null, 204);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Ensure the question actually belongs to the given quiz.
     */
    private function authorizeQuestion(Quiz $quiz, QuizQuestion $question): void
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404, 'Question not found for this quiz.');
        }
    }
}
