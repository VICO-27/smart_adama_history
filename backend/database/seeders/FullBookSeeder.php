<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Services\RAG\IngestionService;
use Illuminate\Support\Facades\DB;

class FullBookSeeder extends Seeder
{
    public function run(IngestionService $ingestionService): void
    {
        $this->command->info('Starting full 451-page Smart Adama Book ingestion...');

        $book = Book::create([
            'title' => 'Smart Adama: A Complete Conceptual Framework',
            'status' => 'published',
            'source_file_path' => '/manuscripts/SA-Book.pdf',
            'source_file_type' => 'pdf'
        ]);

        $chaptersData = [
            [
                'title' => 'Chapter 1: Introduction',
                'sections' => [
                    ['title' => 'Background', 'raw_text' => 'Smart Adama is a foundational digital ecosystem designed to transform urban administration, infrastructure, and citizen services in Adama.'],
                    ['title' => 'Global Insight of Smart City Initiatives', 'raw_text' => 'Global benchmarks show that successful smart cities integrate IoT, data-driven governance, and sustainable frameworks.'],
                    ['title' => 'Smart Adama Initiative Architecture', 'raw_text' => 'The system architecture relies on cloud infrastructure, microservices, and robust vector embeddings for AI processing.'],
                    ['title' => 'System Model for Smart Adama Initiatives', 'raw_text' => 'The architectural model coordinates secure communications between municipal databases, AI tutors, and user interfaces.']
                ]
            ],
            [
                'title' => 'Chapter 2: Limiting Factors and Opportunities',
                'sections' => [
                    ['title' => 'Limiting Factors', 'raw_text' => 'Challenges include digital literacy gaps, bandwidth constraints, and data privacy compliance.'],
                    ['title' => 'Opportunities', 'raw_text' => 'Massive potential exists in youth-driven tech entrepreneurship, automated e-governance, and scalable education platforms.']
                ]
            ],
            [
                'title' => 'Chapter 3: Smart Governance',
                'sections' => [
                    ['title' => 'Introduction to Smart Governance', 'raw_text' => 'Smart governance leverages digital technologies and data-driven approaches to enhance transparency, accountability, and efficiency.'],
                    ['title' => 'Major Activities and Implementation Strategies', 'raw_text' => 'Implementation involves rolling out centralized online portals, digitizing land registry records, and providing automated citizen feedback loops.']
                ]
            ],
            [
                'title' => 'Chapter 4: Digital Adama',
                'sections' => [
                    ['title' => 'Digitization and Digital Transformation', 'raw_text' => 'Transitioning paper-based systems to secure databases ensures long-term operational resilience and instant data access.'],
                    ['title' => 'Implementation Procedures', 'raw_text' => 'Standard operating procedures require strict role-based access control, encrypted data transport, and continuous API monitoring.']
                ]
            ],
            [
                'title' => 'Chapter 5: Smart Security',
                'sections' => [
                    ['title' => 'Modern Technologies for Smart Security', 'raw_text' => 'Smart security uses predictive analytics, automated surveillance networks, and CAD (Computer-Aided Dispatch) systems to safeguard public spaces.'],
                    ['title' => 'Components of a CAD System', 'raw_text' => 'CAD systems integrate emergency dispatch routing, real-time responder tracking, and secure database logging.']
                ]
            ],
            [
                'title' => 'Chapter 6: Smart Urban Design and Land Use Management',
                'sections' => [
                    ['title' => 'Urban Planning Framework', 'raw_text' => 'Geospatial mapping and zoning automation allow city planners to optimize land utilization and green space distribution.'],
                    ['title' => 'Sustainable Infrastructure', 'raw_text' => 'Smart zoning promotes eco-friendly building standards and efficient utility grids across all municipal sectors.']
                ]
            ],
            [
                'title' => 'Chapter 7: Smart Environment and Organic Production',
                'sections' => [
                    ['title' => 'Environmental Monitoring', 'raw_text' => 'IoT sensor networks measure air quality, waste management efficiency, and water purity levels across the city.'],
                    ['title' => 'Organic Production & Food Security', 'raw_text' => 'Data-driven agricultural management systems support local farmers with predictive weather modeling and supply chain logistics.']
                ]
            ],
            [
                'title' => 'Chapter 8: Smart Mobility',
                'sections' => [
                    ['title' => 'Intelligent Transportation Systems', 'raw_text' => 'Smart mobility focuses on real-time traffic management, automated public transit scheduling, and smart parking infrastructure.'],
                    ['title' => 'Fleet Tracking & Logistics', 'raw_text' => 'Centralized dispatch solutions reduce congestion and lower carbon emissions through optimized public transit routing.']
                ]
            ],
            [
                'title' => 'Chapter 9: Smart Social Services',
                'sections' => [
                    ['title' => 'Digital Education and Health', 'raw_text' => 'Integrating AI platforms like Smart Adama provides personalized learning, resource tracking, and remote healthcare access for citizens.'],
                    ['title' => 'Community Welfare', 'raw_text' => 'Digital welfare portals streamline subsidy distribution and emergency assistance tracking for vulnerable groups.']
                ]
            ],
            [
                'title' => 'Chapter 10: Smart Tourism and Culture',
                'sections' => [
                    ['title' => 'Cultural Heritage Preservation', 'raw_text' => 'Virtual reality archives and digital mapping preserve historical landmarks and promote regional tourism.'],
                    ['title' => 'Interactive Tourism Portals', 'raw_text' => 'Mobile applications provide visitors with automated guides, event schedules, and multilingual municipal support.']
                ]
            ],
            [
                'title' => 'Chapter 11: Smart Public Relations and Smart People',
                'sections' => [
                    ['title' => 'Knowledge Management & Developer Ecosystem', 'raw_text' => 'The Smart Adama platform is built by talented computer science interns and developers to foster local tech innovation.'],
                    ['title' => 'Citizen Empowerment', 'raw_text' => 'Empowering citizens with continuous digital training ensures a thriving, highly skilled smart society in Adama.']
                ]
            ]
        ];

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

                    $ingestionService->ingestSection($section);
                    
                    $this->command->info("   -> Embedded '{$sectionData['title']}'. Sleeping 21s for API limits...");
                    sleep(21);
                }

                $chapter->update([
                    'ingestion_status' => 'ready',
                    'ingested_at' => now(),
                ]);

                $this->command->info("Successfully ingested: {$chapter->title}");
            }
            
            DB::commit();
            $this->command->info('Full book successfully indexed and embedded!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Full book ingestion failed: ' . $e->getMessage());
        }
    }
}