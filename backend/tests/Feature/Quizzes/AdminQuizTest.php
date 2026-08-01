<?php

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a valid question payload with 2 options (one correct).
 */
function validQuestionPayload(array $overrides = []): array
{
    return array_merge([
        'question_text' => 'What is the main theme of Smart Adama?',
        'type'          => 'single',
        'explanation'   => 'Discussed in chapter 1.',
        'options'       => [
            ['option_text' => 'Correct answer', 'is_correct' => true,  'order' => 0],
            ['option_text' => 'Wrong answer',   'is_correct' => false, 'order' => 1],
        ],
    ], $overrides);
}

// ── POST /api/v1/admin/chapters/{chapter}/quizzes ─────────────────────────────

it('admin can create a quiz for a chapter', function () {
    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/quizzes", [
            'title'            => 'Chapter 1 Quiz',
            'passing_score_pct' => 75,
        ])
        ->assertStatus(201)
        ->assertJsonPath('quiz.title', 'Chapter 1 Quiz')
        ->assertJsonPath('quiz.passing_score_pct', 75)
        ->assertJsonPath('quiz.status', 'draft');

    $this->assertDatabaseHas('quizzes', [
        'chapter_id'       => $chapter->id,
        'title'            => 'Chapter 1 Quiz',
        'passing_score_pct' => 75,
    ]);
});

it('admin cannot create a second quiz for the same chapter', function () {
    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create();
    Quiz::factory()->create(['chapter_id' => $chapter->id]);

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/quizzes", [
            'title' => 'Duplicate Quiz',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'This chapter already has a quiz.');
});

it('quiz defaults to 70% passing score when not provided', function () {
    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/quizzes", [
            'title' => 'Quiz Without Explicit Threshold',
        ])
        ->assertStatus(201)
        ->assertJsonPath('quiz.passing_score_pct', 70);
});

it('returns 403 when a learner tries to create a quiz', function () {
    $learner = User::factory()->create();
    $chapter = Chapter::factory()->create();

    $this->actingAs($learner)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/quizzes", [
            'title' => 'Unauthorized Quiz',
        ])
        ->assertStatus(403);
});

it('returns 401 when unauthenticated', function () {
    $chapter = Chapter::factory()->create();

    $this->postJson("/api/v1/admin/chapters/{$chapter->id}/quizzes", [
        'title' => 'No Auth Quiz',
    ])->assertStatus(401);
});

// ── POST /api/v1/admin/quizzes/{quiz}/questions ───────────────────────────────

it('admin can add a question with options to a quiz', function () {
    $admin = User::factory()->admin()->create();
    $quiz  = Quiz::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/questions", validQuestionPayload())
        ->assertStatus(201)
        ->assertJsonPath('question.question_text', 'What is the main theme of Smart Adama?')
        ->assertJsonPath('question.type', 'single')
        ->assertJsonStructure(['question' => ['id', 'options']]);

    $this->assertDatabaseHas('quiz_questions', [
        'quiz_id'       => $quiz->id,
        'question_text' => 'What is the main theme of Smart Adama?',
    ]);
});

it('returns 422 when no correct option is provided', function () {
    $admin = User::factory()->admin()->create();
    $quiz  = Quiz::factory()->create();

    $payload = validQuestionPayload([
        'options' => [
            ['option_text' => 'Option A', 'is_correct' => false],
            ['option_text' => 'Option B', 'is_correct' => false],
        ],
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/questions", $payload)
        ->assertStatus(422);

    // Handler wraps validation errors under error.details (see Handler.php)
    $details = $response->json('error.details');
    expect($details)->toHaveKey('options');
    expect($details['options'])->toContain('At least one option must be marked as correct.');
});

it('returns 422 when fewer than 2 options are provided', function () {
    $admin = User::factory()->admin()->create();
    $quiz  = Quiz::factory()->create();

    $payload = validQuestionPayload([
        'options' => [
            ['option_text' => 'Only option', 'is_correct' => true],
        ],
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/questions", $payload)
        ->assertStatus(422);

    // options array must have at least 2 items
    $details = $response->json('error.details');
    expect($details)->toHaveKey('options');
});

// ── PATCH /api/v1/admin/quizzes/{quiz}/questions/{question} ───────────────────

it('admin can update a question on a quiz', function () {
    $admin    = User::factory()->admin()->create();
    $quiz     = Quiz::factory()->create();
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
    QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    QuizOption::factory()->create(['quiz_question_id' => $question->id]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/quizzes/{$quiz->id}/questions/{$question->id}", validQuestionPayload([
            'question_text' => 'Updated question text',
        ]))
        ->assertOk()
        ->assertJsonPath('question.question_text', 'Updated question text');

    $this->assertDatabaseHas('quiz_questions', [
        'id'            => $question->id,
        'question_text' => 'Updated question text',
    ]);
});

it('returns 404 when updating a question that does not belong to the quiz', function () {
    $admin      = User::factory()->admin()->create();
    $quiz       = Quiz::factory()->create();
    $otherQuiz  = Quiz::factory()->create();
    $question   = QuizQuestion::factory()->create(['quiz_id' => $otherQuiz->id]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/quizzes/{$quiz->id}/questions/{$question->id}", validQuestionPayload())
        ->assertStatus(404);
});

// ── DELETE /api/v1/admin/quizzes/{quiz}/questions/{question} ──────────────────

it('admin can delete a question from a quiz', function () {
    $admin    = User::factory()->admin()->create();
    $quiz     = Quiz::factory()->create();
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/quizzes/{$quiz->id}/questions/{$question->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
});

// ── POST /api/v1/admin/quizzes/{quiz}/publish ─────────────────────────────────

it('admin can publish a valid quiz', function () {
    $admin    = User::factory()->admin()->create();
    $quiz     = Quiz::factory()->create(['status' => 'draft']);
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
    QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    QuizOption::factory()->create(['quiz_question_id' => $question->id]);

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/publish")
        ->assertOk()
        ->assertJsonPath('quiz.status', 'published');

    $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'status' => 'published']);
});

it('cannot publish a quiz with no questions', function () {
    $admin = User::factory()->admin()->create();
    $quiz  = Quiz::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/publish")
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'A quiz must have at least one question before publishing.']);
});

it('cannot publish a quiz when a question has fewer than 2 options', function () {
    $admin    = User::factory()->admin()->create();
    $quiz     = Quiz::factory()->create(['status' => 'draft']);
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
    QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    // Only 1 option — should fail

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/publish")
        ->assertStatus(422);
});

it('cannot publish a quiz when a question has no correct option', function () {
    $admin    = User::factory()->admin()->create();
    $quiz     = Quiz::factory()->create(['status' => 'draft']);
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
    QuizOption::factory()->count(2)->create(['quiz_question_id' => $question->id, 'is_correct' => false]);

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/publish")
        ->assertStatus(422);
});

it('cannot publish an already-published quiz', function () {
    $admin    = User::factory()->admin()->create();
    $quiz     = Quiz::factory()->published()->create();
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
    QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    QuizOption::factory()->create(['quiz_question_id' => $question->id]);

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/quizzes/{$quiz->id}/publish")
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Quiz is already published.']);
});

// ── Historical attempts preserved when questions are edited (Req 8.4) ─────────

it('editing a question on a quiz with existing attempts does not alter the attempt', function () {
    $admin    = User::factory()->admin()->create();
    $learner  = User::factory()->create();
    $quiz     = Quiz::factory()->published()->create();
    $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
    QuizOption::factory()->correct()->create(['quiz_question_id' => $question->id]);
    QuizOption::factory()->create(['quiz_question_id' => $question->id]);

    // Simulate an existing attempt
    $attempt = QuizAttempt::factory()->create([
        'user_id'      => $learner->id,
        'quiz_id'      => $quiz->id,
        'score_pct'    => 100,
        'passed'       => true,
        'submitted_at' => now(),
    ]);

    // Admin edits the question — should succeed without touching the attempt
    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/quizzes/{$quiz->id}/questions/{$question->id}", validQuestionPayload([
            'question_text' => 'Edited question after attempt exists',
        ]))
        ->assertOk();

    // Attempt still exists and is unaffected
    $this->assertDatabaseHas('quiz_attempts', [
        'id'        => $attempt->id,
        'score_pct' => 100,
        'passed'    => true,
    ]);
});
