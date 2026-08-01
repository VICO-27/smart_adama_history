<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Books\AdminBookController;
use App\Http\Controllers\Api\V1\Books\AdminChapterController;
use App\Http\Controllers\Api\V1\Books\AdminSectionController;
use App\Http\Controllers\Api\V1\Books\BookController;
use App\Http\Controllers\Api\V1\Books\ChapterController;
use App\Http\Controllers\Api\V1\Chat\ChatMessageController;
use App\Http\Controllers\Api\V1\Chat\ChatSessionController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Gamification\BadgeController;
use App\Http\Controllers\Api\V1\Gamification\StreakController;
use App\Http\Controllers\Api\V1\Health\HealthController;
use App\Http\Controllers\Api\V1\Progress\ProgressController;
use App\Http\Controllers\Api\V1\Quizzes\AdminQuizController;
use App\Http\Controllers\Api\V1\Quizzes\AdminQuizQuestionController;
use App\Http\Controllers\Api\V1\Quizzes\QuizAttemptController;
use App\Http\Controllers\Api\V1\Quizzes\QuizController;
use App\Http\Controllers\Api\V1\Users\UserController;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\ChatController;

Route::prefix('chat')->group(function () {
    Route::post('sessions', [ChatController::class, 'storeSession']);
    Route::post('sessions/{session}/messages', [ChatController::class, 'sendMessage']);
});


Route::get('/health', HealthController::class);

Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);
    Route::post('/password/forgot', [PasswordResetController::class, 'forgot']);
    Route::post('/password/reset', [PasswordResetController::class, 'reset']);
    
    // --- NEW: Socialite Routes ---
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', LogoutController::class);
    Route::get('/auth/me', MeController::class);

    Route::prefix('users/me')->group(function () {
        Route::get('/', [UserController::class, 'show']);
        Route::patch('/', [UserController::class, 'update']);
        
        // --- NEW: Password Update Route ---
        Route::put('/password', [UserController::class, 'updatePassword']);
        
        Route::post('/avatar', [UserController::class, 'uploadAvatar']);
        Route::delete('/', [UserController::class, 'destroy']);
        Route::get('/quiz-attempts', [QuizAttemptController::class, 'index']);
    });

    Route::get('/books', [BookController::class, 'index']);
    Route::get('/chapters/{chapter}', [ChapterController::class, 'show']);
    Route::post('/chapters/{chapter}/read', [ChapterController::class, 'markRead']);
    Route::get('/chapters/{chapter}/quiz', [QuizController::class, 'showByChapter']);

    Route::prefix('chat/sessions')->group(function () {
        Route::get('/', [ChatSessionController::class, 'index']);
        Route::post('/', [ChatSessionController::class, 'store']);
        Route::get('/{session}', [ChatSessionController::class, 'show']);
        Route::patch('/{session}', [ChatSessionController::class, 'update']);
        Route::delete('/{session}', [ChatSessionController::class, 'destroy']);
        Route::post('/{session}/messages', [ChatMessageController::class, 'store'])
            ->middleware('throttle.chat');
    });

    Route::post('/quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store']);
    Route::post('/quizzes/{quiz}/attempts/{attempt}/submit', [QuizAttemptController::class, 'submit']);

    Route::get('/users/me/progress', [ProgressController::class, 'show']);
    Route::get('/users/me/badges', [BadgeController::class, 'index']);
    Route::get('/users/me/streak', [StreakController::class, 'show']);
    Route::get('/dashboard', [DashboardController::class, 'show']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::post('/books', [AdminBookController::class, 'store']);
        Route::get('/books/{book}', [AdminBookController::class, 'show']);
        Route::post('/books/{book}/chapters', [AdminChapterController::class, 'store']);
        Route::patch('/chapters/{chapter}', [AdminChapterController::class, 'update']);
        Route::post('/chapters/{chapter}/publish', [AdminChapterController::class, 'publish']);
        Route::post('/chapters/{chapter}/sections', [AdminSectionController::class, 'store']);
        Route::patch('/sections/{section}', [AdminSectionController::class, 'update']);
        Route::post('/chapters/{chapter}/quizzes', [AdminQuizController::class, 'store']);
        Route::post('/quizzes/{quiz}/questions', [AdminQuizQuestionController::class, 'store']);
        Route::patch('/quizzes/{quiz}/questions/{question}', [AdminQuizQuestionController::class, 'update']);
        Route::delete('/quizzes/{quiz}/questions/{question}', [AdminQuizQuestionController::class, 'destroy']);
        Route::post('/quizzes/{quiz}/publish', [AdminQuizController::class, 'publish']);
        Route::get('/analytics', [\App\Http\Controllers\Api\V1\Dashboard\AdminAnalyticsController::class, 'show']);
    });
});