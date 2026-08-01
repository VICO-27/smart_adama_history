<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('New Chat');
            $table->timestamp('last_activity_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('chat_session_id')->constrained()->cascadeOnDelete();
            $table->string('role');  // user | assistant
            $table->longText('content');
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });

        Schema::create('chat_message_sources', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('chat_message_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('content_chunk_id')->constrained()->cascadeOnDelete();
            $table->float('similarity_score')->default(0);
            $table->timestamps();

            $table->index('chat_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_sources');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};
