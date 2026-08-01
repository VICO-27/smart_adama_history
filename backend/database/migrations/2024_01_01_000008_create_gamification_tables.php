<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('code')->unique(); // machine-readable key e.g. "first_chapter"
            $table->string('name');
            $table->text('description');
            $table->string('icon'); // emoji or icon identifier
            // JSON: { "type": "chapter_count|perfect_score|streak_days|book_complete", "threshold": N }
            $table->json('criteria');
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();

            // A user can earn each badge only once
            $table->unique(['user_id', 'badge_id']);
            $table->index('user_id');
        });

        Schema::create('user_streaks', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
