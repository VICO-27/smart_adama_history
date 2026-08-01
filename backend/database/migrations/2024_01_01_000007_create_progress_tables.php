<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('chapter_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->float('best_quiz_score_pct')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            // One progress record per user+chapter
            $table->unique(['user_id', 'chapter_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
