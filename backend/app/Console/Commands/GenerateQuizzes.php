<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chapter;
use App\Models\Quiz;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GenerateQuizzes extends Command
{
    protected $signature = 'book:generate-quizzes {--force : Override existing quizzes}';
    protected $description = 'Automatically generates 5 multiple-choice questions for each chapter using AI.';

    public function handle()
    {
        $this->info('Starting AI Quiz Generation Pipeline...');

        // CHANGED: Now pulling your Groq key from the .env file
        $apiKey = env('GROQ_API_KEY');
        if (!$apiKey) {
            $this->error('Error: Please add GROQ_API_KEY to your backend/.env file!');
            return;
        }

        $chapters = Chapter::with('sections')->where('title', '!=', 'Introduction & Preface')->get();

        foreach ($chapters as $chapter) {
            $this->info("Processing: {$chapter->title}");

            if ($chapter->quiz()->exists() && !$this->option('force')) {
                $this->warn("Quiz already exists for {$chapter->title}. Skipping. (Use --force to override)");
                continue;
            }

            // Aggregate chapter content to feed to the AI
            $content = $chapter->sections->pluck('raw_text')->join("\n\n");
            
            // Limit content size just to prevent token overflow if a chapter is huge
            $content = substr($content, 0, 15000); 

            if (empty(trim($content))) {
                $this->error("No content found for {$chapter->title}. Skipping.");
                continue;
            }

            // CHANGED: Prompt updated to ask for a strict JSON object for Groq's JSON mode
            $prompt = <<<TEXT
You are an expert curriculum designer. Read the following chapter content and generate exactly 5 multiple-choice questions testing the core concepts.

Return ONLY a JSON object with a "questions" key containing the array. Do not add markdown blocks.
Format:
{
  "questions": [
    {
      "question_text": "Question text here?",
      "type": "single",
      "explanation": "Why this is correct.",
      "options": [
        {"option_text": "First option", "is_correct": true},
        {"option_text": "Second option", "is_correct": false},
        {"option_text": "Third option", "is_correct": false},
        {"option_text": "Fourth option", "is_correct": false}
      ]
    }
  ]
}

Chapter Content:
$content
TEXT;

            try {
                $this->line("Requesting Groq AI generation for {$chapter->title}...");
                
                // CHANGED: Hitting Groq's API directly
                $response = Http::withToken($apiKey)->timeout(60)->post("https://api.groq.com/openai/v1/chat/completions", [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2
                ]);

                if (!$response->successful()) {
                    $this->error("API Request Failed: " . $response->body());
                    continue;
                }

                $responseData = $response->json();
                $jsonStr = $responseData['choices'][0]['message']['content'] ?? '';
                
                $parsed = json_decode($jsonStr, true);
                $questionsData = $parsed['questions'] ?? null;

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($questionsData)) {
                    $this->error("Failed to parse JSON for {$chapter->title}.");
                    Log::error("JSON Parse Error", ['raw_response' => $jsonStr]);
                    continue;
                }

                DB::transaction(function () use ($chapter, $questionsData) {
                    if ($chapter->quiz()->exists()) {
                        $chapter->quiz()->delete(); // Wipe old quiz if forcing
                    }

                    $quiz = Quiz::create([
                        'chapter_id' => $chapter->id,
                        'title' => "Quiz: {$chapter->title}",
                        'passing_score_pct' => 70.00,
                        'status' => 'published',
                    ]);

                    $order = 1;
                    foreach ($questionsData as $qData) {
                        $question = $quiz->questions()->create([
                            'question_text' => $qData['question_text'],
                            'type' => $qData['type'] ?? 'single',
                            'explanation' => $qData['explanation'] ?? '',
                            'order' => $order++,
                        ]);

                        $optOrder = 1;
                        foreach ($qData['options'] as $oData) {
                            $question->options()->create([
                                'option_text' => $oData['option_text'],
                                'is_correct' => $oData['is_correct'],
                                'order' => $optOrder++,
                            ]);
                        }
                    }
                });

                $this->info("✅ Successfully generated and saved quiz for {$chapter->title}!");
                
                // 21-second buffer to prevent rate limiting!
                $this->line("Waiting 21 seconds for API rate limits...");
                sleep(21); 
                
            } catch (\Exception $e) {
                $this->error("Error generating quiz for {$chapter->title}: " . $e->getMessage());
            }
        }

        $this->info('🎉 Quiz generation pipeline complete!');
    }
}