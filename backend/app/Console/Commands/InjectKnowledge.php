<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\ContentChunk;
use App\Services\RAG\ChunkingService;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use Illuminate\Support\Facades\DB;

class InjectKnowledge extends Command
{
    // The command you will run in the terminal
    protected $signature = 'book:inject';
    protected $description = 'Instantly appends new specific knowledge without wiping the database.';

    public function handle(ChunkingService $chunker, EmbeddingProviderInterface $embedder)
    {
        $this->info('🚀 Starting quick knowledge injection...');

        // 1. Grab the existing book
        $book = Book::first();
        if (!$book) {
            $this->error('❌ No book found. You must run php artisan book:parse first to build the base database.');
            return;
        }

        // 2. Find or create a specific chapter just for these manual updates
        $chapter = $book->chapters()->firstOrCreate(
            ['title' => 'System Context: Supplementary AI Knowledge'],
            ['order' => 1000, 'ingestion_status' => 'ready']
        );

        // ------------------------------------------------------------------
        // 3. THE KNOWLEDGE PATCHES
        // These are the exact answers to the questions the AI failed previously.
        // ------------------------------------------------------------------
        $newKnowledge = [
            [
                'title' => 'Platform User Guide and Navigation',
                'text'  => "To navigate the Smart Adama platform, users can use the left sidebar to browse chapters. To take a quiz, click the 'Quizzes' button in the sidebar or at the end of a chapter. To switch to dark mode, click the 'Aa' button in the top toolbar to access the theme settings, where you can select Light, Sepia, or Dark themes."
            ],
            [
                'title' => 'Support and GitHub Reporting',
                'text'  => "If users encounter a bug on the reading interface or anywhere else on the Smart Adama platform, they can report the issue directly to the development team by opening an issue on the official GitHub repository."
            ],
            [
                'title' => 'Project Roadmap and Development Phase',
                'text'  => "Following the successful 30-day summer sprint, the Smart Adama platform is currently in its Beta Launch phase. Future planned features include mobile application support, leaderboards for quizzes, and expanded interactive digital public service modules."
            ],
            [
                'title' => 'Adama City Administrative Divisions',
                'text'  => "Administratively, Adama City is divided into 6 sub-cities and 19 woredas. This structure is supported by 33 sector offices at the city administration level to ensure effective municipal governance."
            ],
            [
                'title' => 'Smart People Definition',
                'text'  => "In the context of Smart Adama, 'Smart People' refers to human capital—the citizens, institutions, and social infrastructure. It emphasizes education, digital skills, and empowering residents to actively participate in the digital ecosystem rather than just focusing on technology."
            ]
        ];

        $allChunks = [];

        // 4. Save these as new sections and chunk the text
        foreach ($newKnowledge as $item) {
            $section = $chapter->sections()->create([
                'title' => $item['title'],
                'order' => $chapter->sections()->count() + 1, // Append to the end
                'raw_text' => $item['text']
            ]);

            $sectionChunks = $chunker->chunk($item['text'], $section->id);
            foreach ($sectionChunks as $c) {
                $allChunks[] = $c;
            }
        }

        if (empty($allChunks)) {
            $this->info('No text to embed.');
            return;
        }

        $this->info("📦 Generated " . count($allChunks) . " new chunks. Embedding instantly...");

        // 5. Send it to Voyage AI (1 API request, virtually instant)
        try {
            $texts = array_column($allChunks, 'chunk_text');
            $embeddings = $embedder->embedBatch($texts);

            DB::transaction(function () use ($allChunks, $embeddings) {
                foreach ($allChunks as $i => $chunkData) {
                    $vector = $embeddings[$i] ?? null;
                    if (!$vector) continue;

                    $contentChunk = ContentChunk::create([
                        'section_id'       => $chunkData['section_id'],
                        'chunk_text'       => $chunkData['chunk_text'],
                        'chunk_index'      => $chunkData['chunk_index'],
                        'token_count'      => $chunkData['token_count'],
                        'embedding_status' => 'ready',
                    ]);

                    $vectorStr = '[' . implode(',', $vector) . ']';
                    DB::statement(
                        'UPDATE content_chunks SET embedding = ? WHERE id = ?',
                        [$vectorStr, $contentChunk->id]
                    );
                }
            });

            $this->info("\n✅ SUCCESS! New knowledge injected instantly into the AI's brain. No need to wait 28 minutes!");

        } catch (\Exception $e) {
            $this->error("\n❌ Injection failed: " . $e->getMessage());
        }
    }
}