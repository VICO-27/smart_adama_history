<?php

use App\Jobs\EvaluateBadgesJob;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Queue;

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a published quiz with one single-choice question (2 options, 1 correct).
 * Returns ['quiz', 'question', 'correct_option', 'wrong_option'].
 */
function publishedQuizWithSingleQuestion(?Chapter $chapter = null): array
{
    $chapter  = $chapter ?? Chapter::factory()->create();
    $quiz     = Quiz::factory()->published()->create(['chapter_id' => $chapter->id, 'passing_score_pct' => 70]);
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'type' => 'single']);
    $correct  = QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    $wrong    = QuizOption::factory()->create(['quiz_question_id' => $question->id, 'is_correct' => false]);

    return compact('quiz', 'question', 'correct', 'wrong', 'chapter');
}

// ── GET /api/v1/chapters/{chapter}/quiz ───────────────────────────────────────

it('learner can fetch a published quiz for a chapter without correct-answer flags', function () {
    $user    = User::factory()->create();
    ['quiz' => $quiz, 'chapter' => $chapter] = publishedQuizWithSingleQuestion();

    $response = $this->actingAs($user)
        ->getJson("/api/v1/chapters/{$chapter->id}/quiz")
        ->assertOk()
        ->assertJsonPath('quiz.id', $quiz->id)
        ->assertJsonPath('quiz.status', 'published');

    // Correct-answer flags must be absent (Req 9.1)
    $options = $response->json('quiz.questions.0.options');
    foreach ($options as $option) {
        expect($option)->not->toHaveKey('is_correct');
    }
});

it('returns null quiz when chapter has no published quiz', function () {
    $user    = User::factory()->create();
    $chapter = Chapter::factory()->create();
    // Draft quiz — should not be visible to learner
    Quiz::factory()->create(['chapter_id' => $chapter->id, 'status' => 'draft']);

    $this->actingAs($user)
        ->getJson("/api/v1/chapters/{$chapter->id}/quiz")
        ->assertOk()
        ->assertJsonPath('quiz', null);
});

it('returns best_attempt when user has a prior submission', function () {
    $user    = User::factory()->create();
    ['quiz' => $quiz, 'chapter' => $chapter] = publishedQuizWithSingleQuestion();

    QuizAttempt::factory()->create([
        'user_id'      => $user->id,
        'quiz_id'      => $quiz->id,
        'score_pct'    => 80,
        'passed'       => true,
        'submitted_at' => now(),
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/chapters/{$chapter->id}/quiz")
        ->assertOk()
        ->assertJsonPath('best_attempt.score_pct', 80)
        ->assertJsonPath('best_attempt.passed', true);
});

// ── POST /api/v1/quizzes/{quiz}/attempts ──────────────────────────────────────

it('learner can start an attempt and receives questions without correct answers', function () {
    $user = User::factory()->create();
    ['quiz' => $quiz] = publishedQuizWithSingleQuestion();

    $response = $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts")
        ->assertStatus(201)
        ->assertJsonStructure(['attempt_id', 'quiz' => ['id', 'questions']]);

    $this->assertDatabaseHas('quiz_attempts', [
        'user_id' => $user->id,
        'quiz_id' => $quiz->id,
    ]);

    // is_correct must not appear in any option (Req 9.1)
    $options = $response->json('quiz.questions.0.options');
    foreach ($options as $option) {
        expect($option)->not->toHaveKey('is_correct');
    }
});

it('returns 404 when attempting a draft quiz', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->create(['status' => 'draft']);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts")
        ->assertStatus(404);
});

it('allows a user to start multiple attempts on the same quiz (Req 9.4)', function () {
    $user = User::factory()->create();
    ['quiz' => $quiz] = publishedQuizWithSingleQuestion();

    $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")->assertStatus(201);
    $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts")->assertStatus(201);

    expect(QuizAttempt::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count())->toBe(2);
});

// ── POST /api/v1/quizzes/{quiz}/attempts/{attempt}/submit ─────────────────────

it('learner can submit an attempt and receives graded results', function () {
    Queue::fake();

    $user = User::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'correct' => $correct] = publishedQuizWithSingleQuestion();

    $attempt = QuizAttempt::factory()->create([
        'user_id'    => $user->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $q->id, 'selected_option_ids' => [$correct->id]],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('attempt.score_pct', 100)
        ->assertJsonPath('attempt.passed', true)
        ->assertJsonPath('attempt.correct_count', 1)
        ->assertJsonStructure(['attempt' => ['per_question']]);
});

it('graded results include per-question correctness and explanation', function () {
    Queue::fake();

    $user = User::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'correct' => $correct] = publishedQuizWithSingleQuestion();

    $attempt = QuizAttempt::factory()->create([
        'user_id'    => $user->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $q->id, 'selected_option_ids' => [$correct->id]],
            ],
        ])
        ->assertOk();

    $perQ = $response->json('attempt.per_question.0');
    expect($perQ['question_id'])->toBe($q->id);
    expect($perQ['is_correct'])->toBeTrue();
    expect($perQ)->toHaveKey('explanation');
    expect($perQ)->toHaveKey('correct_option_ids');
});

it('score is 0 and not passed when all answers are wrong', function () {
    Queue::fake();

    $user = User::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'wrong' => $wrong] = publishedQuizWithSingleQuestion();

    $attempt = QuizAttempt::factory()->create([
        'user_id'    => $user->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $q->id, 'selected_option_ids' => [$wrong->id]],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('attempt.score_pct', 0)
        ->assertJsonPath('attempt.passed', false)
        ->assertJsonPath('badge_evaluation_triggered', false);
});

it('passing triggers chapter completion and EvaluateBadgesJob (Req 9.5, 11.1)', function () {
    Queue::fake();

    $user    = User::factory()->create();
    $chapter = Chapter::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'correct' => $correct] = publishedQuizWithSingleQuestion($chapter);

    $attempt = QuizAttempt::factory()->create([
        'user_id'    => $user->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $q->id, 'selected_option_ids' => [$correct->id]],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('badge_evaluation_triggered', true);

    // Chapter marked complete in user_progress (Req 10.1)
    $this->assertDatabaseHas('user_progress', [
        'user_id'      => $user->id,
        'chapter_id'   => $chapter->id,
        'is_completed' => true,
    ]);

    // EvaluateBadgesJob dispatched (Req 11.1)
    Queue::assertPushed(EvaluateBadgesJob::class, fn ($job) => $job->userId === $user->id);
});

it('failing does not trigger chapter completion or badge evaluation', function () {
    Queue::fake();

    $user    = User::factory()->create();
    $chapter = Chapter::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'wrong' => $wrong] = publishedQuizWithSingleQuestion($chapter);

    $attempt = QuizAttempt::factory()->create([
        'user_id'    => $user->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $q->id, 'selected_option_ids' => [$wrong->id]],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('attempt.passed', false);

    $this->assertDatabaseMissing('user_progress', [
        'user_id'    => $user->id,
        'chapter_id' => $chapter->id,
    ]);

    Queue::assertNotPushed(EvaluateBadgesJob::class);
});

it('retaking a quiz stores best score in user_progress (Req 9.4)', function () {
    Queue::fake();

    $user    = User::factory()->create();
    $chapter = Chapter::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'correct' => $correct] = publishedQuizWithSingleQuestion($chapter);

    // First attempt — pass with 100%
    $attempt1 = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'started_at' => now()]);
    $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt1->id}/submit", [
        'answers' => [['question_id' => $q->id, 'selected_option_ids' => [$correct->id]]],
    ]);

    // Second attempt — also pass
    $attempt2 = QuizAttempt::factory()->create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'started_at' => now()]);
    $this->actingAs($user)->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt2->id}/submit", [
        'answers' => [['question_id' => $q->id, 'selected_option_ids' => [$correct->id]]],
    ]);

    $progress = UserProgress::where('user_id', $user->id)->where('chapter_id', $chapter->id)->first();
    expect($progress->best_quiz_score_pct)->toBe(100.0);

    // Both attempts stored independently
    expect(QuizAttempt::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count())->toBe(2);
});

it('cannot submit an already-submitted attempt', function () {
    Queue::fake();

    $user = User::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'correct' => $correct] = publishedQuizWithSingleQuestion();

    $attempt = QuizAttempt::factory()->create([
        'user_id'      => $user->id,
        'quiz_id'      => $quiz->id,
        'started_at'   => now(),
        'submitted_at' => now(), // already submitted
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $q->id, 'selected_option_ids' => [$correct->id]],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'This attempt has already been submitted.']);
});

it('cannot submit another user\'s attempt', function () {
    Queue::fake();

    $user  = User::factory()->create();
    $other = User::factory()->create();
    ['quiz' => $quiz, 'question' => $q, 'correct' => $correct] = publishedQuizWithSingleQuestion();

    $attempt = QuizAttempt::factory()->create([
        'user_id'    => $other->id,
        'quiz_id'    => $quiz->id,
        'started_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit", [
            'answers' => [
                ['question_id' => $q->id, 'selected_option_ids' => [$correct->id]],
            ],
        ])
        ->assertStatus(403);
});

it('returns 401 when unauthenticated user tries to start an attempt', function () {
    ['quiz' => $quiz] = publishedQuizWithSingleQuestion();

    $this->postJson("/api/v1/quizzes/{$quiz->id}/attempts")->assertStatus(401);
});

// ── GET /api/v1/users/me/quiz-attempts ───────────────────────────────────────

it('lists all quiz attempts for the authenticated user', function () {
    $user = User::factory()->create();
    ['quiz' => $quiz] = publishedQuizWithSingleQuestion();

    QuizAttempt::factory()->count(3)->create([
        'user_id'      => $user->id,
        'quiz_id'      => $quiz->id,
        'submitted_at' => now(),
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/users/me/quiz-attempts')
        ->assertOk()
        ->assertJsonCount(3, 'attempts')
        ->assertJsonStructure([
            'attempts' => [['id', 'quiz_id', 'quiz_title', 'score_pct', 'passed', 'submitted_at']],
            'meta'     => ['current_page', 'last_page', 'total'],
        ]);
});

it('does not list quiz attempts belonging to other users', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    ['quiz' => $quiz] = publishedQuizWithSingleQuestion();

    QuizAttempt::factory()->count(2)->create(['user_id' => $other->id, 'quiz_id' => $quiz->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/users/me/quiz-attempts')
        ->assertOk()
        ->assertJsonCount(0, 'attempts');
});
