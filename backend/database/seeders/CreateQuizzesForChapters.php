<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Chapter;

class CreateQuizzesForChapters extends Seeder
{
    public function run(): void
    {
        $chapters = Chapter::where('ingestion_status', 'ready')->get();
        
        $this->command->info("Found {$chapters->count()} ready chapters");

        foreach ($chapters as $chapter) {
            // Check if quiz exists for this chapter
            $quiz = Quiz::where('chapter_id', $chapter->id)->first();
            
            if (!$quiz) {
                // Create new quiz
                $quiz = Quiz::create([
                    'chapter_id' => $chapter->id,
                    'title' => 'Chapter ' . $chapter->order . ': ' . $chapter->title,
                    'passing_score_pct' => 70,
                    'status' => 'published',
                ]);
                
                $this->createQuestions($quiz, $chapter);
                $this->command->info("   -> Created quiz: {$quiz->title}");
            } else {
                $this->command->info("   -> Quiz already exists: {$quiz->title}");
            }
        }

        $this->command->info('Done! Created/verified ' . Quiz::count() . ' quizzes');
    }

    private function createQuestions(Quiz $quiz, $chapter): void
    {
        $questions = [
            [
                'question_text' => 'What is the primary purpose of the chapter "' . $chapter->title . '"?',
                'type' => 'single',
                'order' => 1,
                'options' => [
                    ['option_text' => 'To introduce the Smart Adama concept', 'is_correct' => true],
                    ['option_text' => 'To explain smart governance only', 'is_correct' => false],
                    ['option_text' => 'To describe urban design only', 'is_correct' => false],
                    ['option_text' => 'To promote tourism', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'Which of the following is a key benefit mentioned in this chapter?',
                'type' => 'single',
                'order' => 2,
                'options' => [
                    ['option_text' => 'Automated e-governance', 'is_correct' => true],
                    ['option_text' => 'Higher software licensing costs', 'is_correct' => false],
                    ['option_text' => 'Reduced citizen engagement', 'is_correct' => false],
                    ['option_text' => 'Decreased digital literacy', 'is_correct' => false],
                ],
            ],
        ];

        foreach ($questions as $questionData) {
            $question = QuizQuestion::firstOrCreate(
                ['quiz_id' => $quiz->id, 'question_text' => $questionData['question_text']],
                [
                    'type' => $questionData['type'],
                    'order' => $questionData['order'],
                ]
            );

            foreach ($questionData['options'] as $optionData) {
                $question->options()->firstOrCreate(
                    ['option_text' => $optionData['option_text']],
                    [
                        'order' => $optionData['is_correct'] ? 1 : 2,
                        'is_correct' => $optionData['is_correct'],
                    ]
                );
            }
        }
    }
}
