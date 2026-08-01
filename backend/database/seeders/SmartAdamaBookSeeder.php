<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\Section;
use App\Services\RAG\IngestionService;
use Illuminate\Support\Facades\DB;

class SmartAdamaBookSeeder extends Seeder
{
    public function run(IngestionService $ingestionService): void
    {
        $this->command->info('Starting Smart Adama Book ingestion. This may take a moment to fetch vector embeddings...');

        // 1. Create the Main Book
        $book = Book::create([
            'title' => 'Smart Adama: A Conceptual Framework',
            'status' => 'published',
            'source_file_path' => '/manuscripts/SA-Book.pdf',
            'source_file_type' => 'pdf'
        ]);

        // 2. Define the Real Book Content Structure
        $chaptersData = [
            [
                'title' => 'Core 01: e-Governance',
                'sections' => [
                    [
                        'title' => 'Digital Administration',
                        'raw_text' => "Digital administration forms the backbone of e-Governance in Smart Adama. It enables public institutions to deliver civic services faster and more effectively through integrated systems, online portals, and automated workflows. This reduces bureaucratic delays and ensures high availability of government services to all citizens."
                    ],
                    [
                        'title' => 'Citizen Engagement & Security',
                        'raw_text' => "Engaging citizens through digital channels fosters transparency and builds trust between the government and the community. Smart security protocols ensure that civic data is encrypted, protected against cyber threats, and utilized ethically to improve urban management."
                    ]
                ]
            ],
            [
                'title' => 'Core 02: Innovation',
                'sections' => [
                    [
                        'title' => 'Technological Research',
                        'raw_text' => "Innovation is driven by continuous technological research. Smart Adama aims to foster environments where universities, researchers, and tech enthusiasts can collaborate on breakthrough technologies, artificial intelligence, and smart city infrastructure."
                    ]
                ]
            ],
            [
                'title' => 'Core 03: Enterprise',
                'sections' => [
                    [
                        'title' => 'Supporting Startups',
                        'raw_text' => "The Enterprise pillar focuses heavily on local digital economic growth. By providing digital infrastructure, funding avenues, and regulatory support, Smart Adama supports local tech startups and small-to-medium enterprises (SMEs) to scale their operations globally."
                    ]
                ]
            ],
            [
                'title' => 'Core 04: Food Security',
                'sections' => [
                    [
                        'title' => 'Smart Agriculture',
                        'raw_text' => "To ensure long-term sustainability, Food Security relies on smart agricultural management. This includes using IoT sensors to monitor crop health, automated irrigation systems, and data-driven supply chain management to eliminate food waste and ensure equitable distribution."
                    ]
                ]
            ]
        ];

        // 3. Process and Ingest Data
        // 3. Process and Ingest Data
        DB::beginTransaction();
        try {
            foreach ($chaptersData as $chapterIndex => $chapterData) {
                
                $chapter = $book->chapters()->create([
                    'title' => $chapterData['title'],
                    'order' => $chapterIndex + 1,
                    'ingestion_status' => 'processing'
                ]);

                foreach ($chapterData['sections'] as $sectionIndex => $sectionData) {
                    $section = $chapter->sections()->create([
                        'title' => $sectionData['title'],
                        'order' => $sectionIndex + 1,
                        'raw_text' => $sectionData['raw_text']
                    ]);

                    // Call the RAG service synchronously to chunk and embed immediately
                    $ingestionService->ingestSection($section);
                    
                    // --- RATE LIMIT PROTECTION ---
                    // Voyage AI free tier allows exactly 3 requests per minute.
                    // We sleep for 21 seconds to stay safely under the radar.
                    $this->command->info("   -> Embedded '{$sectionData['title']}'. Sleeping 21s to respect API limits...");
                    sleep(21);
                }

                // Mark chapter as fully ingested
                $chapter->update([
                    'ingestion_status' => 'ready',
                    'ingested_at' => now(),
                ]);

                $this->command->info("Successfully ingested: {$chapter->title}");
            }
            
            DB::commit();
            $this->command->info('All chapters successfully chunked, embedded, and saved!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Ingestion failed: ' . $e->getMessage());
        }
    }
}