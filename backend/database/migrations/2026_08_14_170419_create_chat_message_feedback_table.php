<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_message_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chat_message_id');
            $table->uuid('user_id')->nullable();
            $table->enum('feedback', ['like', 'dislike']);
            $table->timestamps();

            $table->foreign('chat_message_id')->references('id')->on('chat_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['chat_message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_feedback');
    }
};
