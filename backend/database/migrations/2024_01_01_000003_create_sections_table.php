<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->longText('raw_text')->nullable();
            $table->timestamps();

            $table->index(['chapter_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
