<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Quiz\QuizGradingService;

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a quiz with n questions each having one correct + one wrong option.
 * Returns the quiz and an array of ['question', 'correct', 'wrong'] entries.
 */
function buildGradableQuiz(int $questionCount = 1, string $type = 'single'): array
{
    $quiz      = Quiz::factory()->create(['passing_score_pct' => 70]);
    $questions = [];

    for ($i = 0; $i < $questionCount; $i++) {
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'type' => $type]);
        $correct  = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
        $wrong    = QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);
        $questions[] = compact('question', 'correct', 'wrong');
    }

    return compact('quiz', 'questions');
}

function makeAttempt(Quiz $quiz): QuizAttempt
{
    $user = User::factory()->create();
    return QuizAttempt::factory()->create([
        'user_id'    => $user->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);
}

// ── Single-choice grading ─────────────────────────────────────────────────────

it('grades a fully correct single-choice attempt as 100%', function () {
    $service = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 1, type: 'single');
    $attempt = makeAttempt($quiz);

    $result = $service->grade($attempt, [
        [
            'question_id'        => $questions[0]['question']->id,
            'selected_option_ids' => [$questions[0]['correct']->id],
        ],
    ]);

    expect($result['score_pct'])->toBe(100.0)
        ->and($result['passed'])->toBeTrue()
        ->and($result['correct_count'])->toBe(1)
        ->and($result['total_questions'])->toBe(1);
});

it('grades a fully wrong single-choice attempt as 0%', function () {
    $service = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 1, type: 'single');
    $attempt = makeAttempt($quiz);

    $result = $service->grade($attempt, [
        [
            'question_id'        => $questions[0]['question']->id,
            'selected_option_ids' => [$questions[0]['wrong']->id],
        ],
    ]);

    expect($result['score_pct'])->toBe(0.0)
        ->and($result['passed'])->toBeFalse()
        ->and($result['correct_count'])->toBe(0);
});

it('computes partial score correctly across multiple questions', function () {
    $service = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 4, type: 'single');
    $attempt = makeAttempt($quiz);

    // Answer 3 out of 4 correctly
    $answers = [
        ['question_id' => $questions[0]['question']->id, 'selected_option_ids' => [$questions[0]['correct']->id]],
        ['question_id' => $questions[1]['question']->id, 'selected_option_ids' => [$questions[1]['correct']->id]],
        ['question_id' => $questions[2]['question']->id, 'selected_option_ids' => [$questions[2]['correct']->id]],
        ['question_id' => $questions[3]['question']->id, 'selected_option_ids' => [$questions[3]['wrong']->id]],
    ];

    $result = $service->grade($attempt, $answers);

    expect($result['score_pct'])->toBe(75.0)
        ->and($result['correct_count'])->toBe(3)
        ->and($result['passed'])->toBeTrue(); // 75% >= 70% passing threshold
});

// ── true_false grading ────────────────────────────────────────────────────────

it('grades a correct true/false answer', function () {
    $service = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 1, type: 'true_false');
    $attempt = makeAttempt($quiz);

    $result = $service->grade($attempt, [
        [
            'question_id'        => $questions[0]['question']->id,
            'selected_option_ids' => [$questions[0]['correct']->id],
        ],
    ]);

    expect($result['score_pct'])->toBe(100.0)
        ->and($result['passed'])->toBeTrue();
});

it('is_correct is false when two options selected for a single-choice question', function () {
    $service  = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 1, type: 'single');
    $attempt  = makeAttempt($quiz);

    $result = $service->grade($attempt, [
        [
            'question_id'        => $questions[0]['question']->id,
            'selected_option_ids' => [
                $questions[0]['correct']->id,
                $questions[0]['wrong']->id,
            ],
        ],
    ]);

    expect($result['score_pct'])->toBe(0.0);
    expect($result['per_question'][0]['is_correct'])->toBeFalse();
});

// ── Multiple-choice grading ───────────────────────────────────────────────────

it('grades multiple-choice correctly when all correct IDs are selected', function () {
    $service  = new QuizGradingService();
    $quiz     = Quiz::factory()->create(['passing_score_pct' => 70]);
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'type' => 'multiple']);
    $opt1     = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    $opt2     = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    $opt3     = QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);
    $attempt  = makeAttempt($quiz);

    $result = $service->grade($attempt, [
        [
            'question_id'        => $question->id,
            'selected_option_ids' => [$opt1->id, $opt2->id],
        ],
    ]);

    expect($result['score_pct'])->toBe(100.0)
        ->and($result['per_question'][0]['is_correct'])->toBeTrue();
});

it('grades multiple-choice as wrong when an extra wrong option is included', function () {
    $service  = new QuizGradingService();
    $quiz     = Quiz::factory()->create(['passing_score_pct' => 70]);
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'type' => 'multiple']);
    $opt1     = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    $opt2     = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    $opt3     = QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);
    $attempt  = makeAttempt($quiz);

    $result = $service->grade($attempt, [
        [
            'question_id'        => $question->id,
            'selected_option_ids' => [$opt1->id, $opt2->id, $opt3->id], // extra wrong option
        ],
    ]);

    expect($result['per_question'][0]['is_correct'])->toBeFalse();
});

// ── Persistence ───────────────────────────────────────────────────────────────

it('persists attempt answers to the database after grading (Req 9.3)', function () {
    $service = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 2, type: 'single');
    $attempt = makeAttempt($quiz);

    $service->grade($attempt, [
        ['question_id' => $questions[0]['question']->id, 'selected_option_ids' => [$questions[0]['correct']->id]],
        ['question_id' => $questions[1]['question']->id, 'selected_option_ids' => [$questions[1]['wrong']->id]],
    ]);

    expect(QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count())->toBe(2);

    expect(QuizAttemptAnswer::where([
        'quiz_attempt_id'  => $attempt->id,
        'quiz_question_id' => $questions[0]['question']->id,
        'is_correct'       => true,
    ])->exists())->toBeTrue();

    expect(QuizAttemptAnswer::where([
        'quiz_attempt_id'  => $attempt->id,
        'quiz_question_id' => $questions[1]['question']->id,
        'is_correct'       => false,
    ])->exists())->toBeTrue();
});

it('persists score_pct, passed, and submitted_at on the attempt (Req 9.3)', function () {
    $service = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 1, type: 'single');
    $attempt = makeAttempt($quiz);

    $service->grade($attempt, [
        ['question_id' => $questions[0]['question']->id, 'selected_option_ids' => [$questions[0]['correct']->id]],
    ]);

    $attempt->refresh();
    expect($attempt->score_pct)->toBe(100.0)
        ->and($attempt->passed)->toBeTrue()
        ->and($attempt->submitted_at)->not->toBeNull();
});

// ── Threshold boundary ────────────────────────────────────────────────────────

it('marks as passed when score equals the passing threshold exactly', function () {
    $service = new QuizGradingService();
    // 70% threshold, 10 questions — answer exactly 7 correctly
    $quiz     = Quiz::factory()->create(['passing_score_pct' => 70]);
    $questions = [];

    for ($i = 0; $i < 10; $i++) {
        $question    = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'type' => 'single']);
        $correct     = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
        $wrong       = QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);
        $questions[] = compact('question', 'correct', 'wrong');
    }

    $attempt = makeAttempt($quiz);

    $answers = [];
    foreach ($questions as $idx => $q) {
        $answers[] = [
            'question_id'        => $q['question']->id,
            'selected_option_ids' => [$idx < 7 ? $q['correct']->id : $q['wrong']->id],
        ];
    }

    $result = $service->grade($attempt, $answers);

    expect($result['score_pct'])->toBe(70.0)
        ->and($result['passed'])->toBeTrue();
});

it('marks as not passed when score is one point below the threshold', function () {
    $service  = new QuizGradingService();
    // 70% threshold, 10 questions — answer only 6 correctly (60%)
    $quiz     = Quiz::factory()->create(['passing_score_pct' => 70]);
    $questions = [];

    for ($i = 0; $i < 10; $i++) {
        $question    = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'type' => 'single']);
        $correct     = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
        $wrong       = QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);
        $questions[] = compact('question', 'correct', 'wrong');
    }

    $attempt = makeAttempt($quiz);

    $answers = [];
    foreach ($questions as $idx => $q) {
        $answers[] = [
            'question_id'        => $q['question']->id,
            'selected_option_ids' => [$idx < 6 ? $q['correct']->id : $q['wrong']->id],
        ];
    }

    $result = $service->grade($attempt, $answers);

    expect($result['score_pct'])->toBe(60.0)
        ->and($result['passed'])->toBeFalse();
});

// ── Unknown question IDs are silently skipped ─────────────────────────────────

it('skips answers with unknown question IDs and does not throw', function () {
    $service = new QuizGradingService();
    ['quiz' => $quiz, 'questions' => $questions] = buildGradableQuiz(questionCount: 1, type: 'single');
    $attempt = makeAttempt($quiz);

    $result = $service->grade($attempt, [
        ['question_id' => $questions[0]['question']->id, 'selected_option_ids' => [$questions[0]['correct']->id]],
        ['question_id' => 'non-existent-uuid-1234-5678',  'selected_option_ids' => []],
    ]);

    // The valid question is graded; the ghost answer is ignored
    expect($result['correct_count'])->toBe(1)
        ->and($result['total_questions'])->toBe(1);
});
