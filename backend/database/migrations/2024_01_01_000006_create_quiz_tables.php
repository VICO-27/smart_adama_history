<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('passing_score_pct')->default(70);
            $table->string('status')->default('draft'); // draft | published
            $table->timestamps();

            $table->index('chapter_id');
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->string('type'); // single | multiple | true_false
            $table->text('explanation')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'order']);
        });

        Schema::create('quiz_options', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('quiz_question_id');
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('quiz_id')->constrained()->cascadeOnDelete();
            $table->float('score_pct')->nullable();
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'quiz_id']);
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('quiz_question_id')->constrained()->cascadeOnDelete();
            // Stored as JSON array of selected option UUIDs
            $table->json('selected_option_ids')->default('[]');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->index('quiz_attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
