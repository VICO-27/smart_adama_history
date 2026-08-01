<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'chapter_id'       => Chapter::factory(),
            'title'            => fake()->sentence(4),
            'passing_score_pct' => 70,
            'status'           => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }
}
