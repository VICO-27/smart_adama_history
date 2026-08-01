<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            // draft | queued | processing | ready | failed
            $table->string('ingestion_status')->default('draft');
            $table->timestamp('ingested_at')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
