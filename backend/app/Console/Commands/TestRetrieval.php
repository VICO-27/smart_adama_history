<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RAG\RetrievalService;

class TestRetrieval extends Command
{
    protected $signature = 'book:test {query}';
    protected $description = 'Inspect what chunks pgvector retrieves for a question.';

    public function handle(RetrievalService $retrieval)
    {
        $query = $this->argument('query');
        $this->info("🔍 Searching vector database for: \"{$query}\"");

        $result = $retrieval->retrieve($query, 5);
        $chunks = $result['chunks'] ?? [];

        if (empty($chunks)) {
            $this->error("❌ THE AI DOES NOT KNOW THIS: Zero relevant chunks were found in the database.");
            return;
        }

        $this->info("✅ The AI found " . count($chunks) . " matching paragraphs in the book:\n");

        foreach ($chunks as $i => $chunk) {
            $rank = $i + 1;
            $this->line("<fg=yellow>--- Chunk #{$rank} [Similarity Match: {$chunk['similarity']}] ---</>");
            $this->line("<b>Chapter:</b> {$chunk['chapter_title']} | <b>Section:</b> {$chunk['section_title']}");
            $this->comment(substr($chunk['chunk_text'], 0, 200) . "...\n");
        }
    }
}