<?php

namespace App\Http\Controllers\Api\V1\Quizzes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quizzes\SubmitQuizAttemptRequest;
use App\Jobs\EvaluateBadgesJob;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\Progress\ProgressService;
use App\Services\Quiz\QuizGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Quiz attempt lifecycle: start → submit → grade (Req 9.1 – 9.5)
 */
class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizGradingService $gradingService,
        private readonly ProgressService $progressService,
    ) {
    }

    /**
     * GET /users/me/quiz-attempts
     * List all quiz attempts for the authenticated user, most recent first.
     */
    public function index(Request $request): JsonResponse
    {
        $attempts = $request->user()
            ->quizAttempts()
            ->with('quiz:id,title,chapter_id,passing_score_pct')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'attempts' => $attempts->map(fn ($a) => [
                'id'           => $a->id,
                'quiz_id'      => $a->quiz_id,
                'quiz_title'   => $a->quiz->title ?? null,
                'score_pct'    => $a->score_pct,
                'passed'       => $a->passed,
                'started_at'   => $a->started_at,
                'submitted_at' => $a->submitted_at,
            ]),
            'meta' => [
                'current_page' => $attempts->currentPage(),
                'last_page'    => $attempts->lastPage(),
                'total'        => $attempts->total(),
            ],
        ]);
    }

    /**
     * POST /quizzes/{quiz}/attempts
     * Start a new attempt for the given published quiz (Req 9.1).
     * Multiple attempts are allowed; each is stored independently (Req 9.4).
     */
    public function store(Request $request, Quiz $quiz): JsonResponse
    {
        if ($quiz->status !== 'published') {
            return response()->json(['message' => 'This quiz is not available.'], 404);
        }

        $attempt = QuizAttempt::create([
            'user_id'    => $request->user()->id,
            'quiz_id'    => $quiz->id,
            'started_at' => now(),
        ]);

        // Return quiz questions without correct-answer flags (Req 9.1)
        $quiz->load('questions.options');

        return response()->json([
            'attempt_id' => $attempt->id,
            'quiz'       => [
                'id'                => $quiz->id,
                'title'             => $quiz->title,
                'passing_score_pct' => $quiz->passing_score_pct,
                'questions'         => $quiz->questions->map(fn ($q) => [
                    'id'            => $q->id,
                    'question_text' => $q->question_text,
                    'type'          => $q->type,
                    'order'         => $q->order,
                    'options'       => $q->options->map(fn ($o) => [
                        'id'          => $o->id,
                        'option_text' => $o->option_text,
                        'order'       => $o->order,
                        // is_correct intentionally omitted (Req 9.1)
                    ]),
                ]),
            ],
        ], 201);
    }

    /**
     * POST /quizzes/{quiz}/attempts/{attempt}/submit
     * Grade the attempt, persist results, update progress, dispatch badge evaluation (Req 9.2 – 9.5).
     */
    public function submit(
        SubmitQuizAttemptRequest $request,
        Quiz $quiz,
        QuizAttempt $attempt,
    ): JsonResponse {
        // Ensure the attempt belongs to this quiz and this user
        if ($attempt->quiz_id !== $quiz->id) {
            return response()->json(['message' => 'Attempt does not belong to this quiz.'], 422);
        }

        if ($attempt->user_id !== $request->user()->id) {
            abort(403, 'You do not own this attempt.');
        }

        if ($attempt->submitted_at !== null) {
            return response()->json(['message' => 'This attempt has already been submitted.'], 422);
        }

        // Grade server-side (Req 9.2, 9.3)
        $result = $this->gradingService->grade($attempt, $request->answers);

        // If passed: mark chapter as completed and dispatch badge evaluation (Req 9.5, 11.1)
        if ($result['passed']) {
            $quiz->loadMissing('chapter');

            $this->progressService->markChapterComplete(
                $request->user(),
                $quiz->chapter,
                $result['score_pct'],
            );

            EvaluateBadgesJob::dispatch($request->user()->id);
        }

        return response()->json([
            'attempt' => [
                'id'              => $attempt->id,
                'quiz_id'         => $quiz->id,
                'score_pct'       => $result['score_pct'],
                'passed'          => $result['passed'],
                'total_questions' => $result['total_questions'],
                'correct_count'   => $result['correct_count'],
                'submitted_at'    => $attempt->fresh()->submitted_at,
                'per_question'    => $result['per_question'],
            ],
            // Req 11.2 — flag newly triggered badge evaluation so the frontend
            // can poll or animate once the job resolves
            'badge_evaluation_triggered' => $result['passed'],
        ]);
    }
}
