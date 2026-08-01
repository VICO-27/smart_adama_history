<?php

namespace App\Services\Quiz;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizOption;
use Illuminate\Support\Collection;

/**
 * Grades a quiz attempt server-side and persists per-question results.
 * Requirements 9.2, 9.3, 9.5
 */
class QuizGradingService
{
    /**
     * Grade the attempt.
     *
     * @param  QuizAttempt  $attempt
     * @param  array<int, array{question_id: string, selected_option_ids: string[]}>  $answers
     * @return array{
     *     score_pct: float,
     *     passed: bool,
     *     total_questions: int,
     *     correct_count: int,
     *     per_question: array,
     * }
     */
    public function grade(QuizAttempt $attempt, array $answers): array
    {
        $quiz      = $attempt->quiz()->with('questions.options')->firstOrFail();
        $questions = $quiz->questions->keyBy('id');

        $correctCount   = 0;
        $totalQuestions = $questions->count();
        $perQuestion    = [];

        foreach ($answers as $answer) {
            $questionId        = $answer['question_id'];
            $selectedOptionIds = $answer['selected_option_ids'] ?? [];

            $question = $questions->get($questionId);

            if (! $question) {
                continue;
            }

            // Get the correct option IDs for this question
            $correctOptionIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values()
                ->toArray();

            $selectedSorted = collect($selectedOptionIds)->sort()->values()->toArray();

            // For single/true_false: exactly one must match
            // For multiple: all correct IDs must be selected, none wrong
            $isCorrect = match ($question->type) {
                'single', 'true_false' => count($selectedSorted) === 1
                    && $selectedSorted[0] === ($correctOptionIds[0] ?? null),
                'multiple' => $selectedSorted === $correctOptionIds,
                default    => false,
            };

            if ($isCorrect) {
                $correctCount++;
            }

            // Persist the answer (Req 9.3)
            QuizAttemptAnswer::create([
                'quiz_attempt_id'    => $attempt->id,
                'quiz_question_id'   => $questionId,
                'selected_option_ids' => $selectedOptionIds,
                'is_correct'         => $isCorrect,
            ]);

            $perQuestion[] = [
                'question_id'        => $questionId,
                'question_text'      => $question->question_text,
                'is_correct'         => $isCorrect,
                'selected_option_ids' => $selectedOptionIds,
                'correct_option_ids' => $correctOptionIds,
                'explanation'        => $question->explanation,
            ];
        }

        $scorePct = $totalQuestions > 0
            ? round(($correctCount / $totalQuestions) * 100, 2)
            : 0;

        $passed = $scorePct >= $quiz->passing_score_pct;

        // Persist score on the attempt
        $attempt->update([
            'score_pct'    => $scorePct,
            'passed'       => $passed,
            'submitted_at' => now(),
        ]);

        return [
            'score_pct'       => $scorePct,
            'passed'          => $passed,
            'total_questions' => $totalQuestions,
            'correct_count'   => $correctCount,
            'per_question'    => $perQuestion,
        ];
    }
}
