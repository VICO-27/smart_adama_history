<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Add section_number column - nullable initially for backward compatibility
            $table->string('section_number')->nullable()->after('title');
        });
        
        // Create unique index on chapter_id + section_number
        // In PostgreSQL, NULL values are not considered equal, so multiple rows can have NULL
        DB::statement('
            CREATE UNIQUE INDEX sections_chapter_section_number_idx 
            ON sections(chapter_id, section_number)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Drop the column first (which also drops the index)
            $table->dropColumn('section_number');
        });
    }
};