<?php

namespace Database\Factories;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizOptionFactory extends Factory
{
    protected $model = QuizOption::class;

    public function definition(): array
    {
        return [
            'quiz_question_id' => QuizQuestion::factory(),
            'option_text'      => fake()->sentence(4),
            'is_correct'       => false,
            'order'            => fake()->numberBetween(1, 4),
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}
