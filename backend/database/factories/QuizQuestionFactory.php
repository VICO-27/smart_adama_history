<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        return [
            'quiz_id'       => Quiz::factory(),
            'question_text' => fake()->sentence() . '?',
            'type'          => fake()->randomElement(['single', 'multiple', 'true_false']),
            'explanation'   => fake()->sentence(),
            'order'         => fake()->numberBetween(1, 20),
        ];
    }
}
