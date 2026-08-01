<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'quiz_id'      => Quiz::factory(),
            'score_pct'    => null,
            'passed'       => false,
            'started_at'   => now(),
            'submitted_at' => null,
        ];
    }

    /**
     * An attempt that has been submitted and graded.
     */
    public function submitted(float $scorePct = 100.0, bool $passed = true): static
    {
        return $this->state([
            'score_pct'    => $scorePct,
            'passed'       => $passed,
            'submitted_at' => now(),
        ]);
    }
}
