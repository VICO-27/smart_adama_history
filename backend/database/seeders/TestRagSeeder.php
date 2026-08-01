<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Services\RAG\IngestionService;

class TestRagSeeder extends Seeder
{
    public function run(IngestionService $ingestionService): void
    {
        $book = Book::create([
            'title' => 'Test Book', 
            'status' => 'published'
        ]);

        $chapter = $book->chapters()->create([
            'title' => 'Test Chapter', 
            'order' => 1, 
            'ingestion_status' => 'processing'
        ]);

        $section = $chapter->sections()->create([
            'title' => 'Test Section', 
            'order' => 1, 
            'raw_text' => 'Smart Adama is an advanced educational platform utilizing embeddings and Groq AI for interactive learning.'
        ]);

        // Run ingestion synchronously to bypass queue driver configuration issues
        $ingestionService->ingestSection($section);

        $chapter->update([
            'ingestion_status' => 'ready',
            'ingested_at' => now(),
        ]);

        $this->command->info('Test book and section ingested successfully with vectors!');
    }
}