<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Get dimension from the active embedding provider config
        $embeddingProvider = config('ai.embedding_provider', 'voyage');
        $dimension = match ($embeddingProvider) {
            'openai' => (int) config('ai.openai.dimension', 1536),
            default  => (int) config('ai.voyage.dimension', 1024),
        };

        Schema::create('content_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('section_id')->constrained()->cascadeOnDelete();
            $table->longText('chunk_text');
            $table->unsignedInteger('chunk_index')->default(0);
            $table->unsignedInteger('token_count')->default(0);
            $table->string('embedding_status')->default('pending'); // pending | ready | failed
            $table->timestamps();

            $table->index(['section_id', 'chunk_index']);
        });

        // Add the pgvector column — Blueprint doesn't support custom types natively
        DB::statement("ALTER TABLE content_chunks ADD COLUMN embedding vector({$dimension})");

        // HNSW index for fast approximate nearest-neighbour cosine search
        // Using cosine distance operator <=> (vector_cosine_ops)
        DB::statement(
            "CREATE INDEX content_chunks_embedding_hnsw_idx
             ON content_chunks
             USING hnsw (embedding vector_cosine_ops)
             WITH (m = 16, ef_construction = 64)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('content_chunks');
    }
};
