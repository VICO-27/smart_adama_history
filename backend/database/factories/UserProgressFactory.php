<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProgressFactory extends Factory
{
    protected $model = UserProgress::class;

    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'chapter_id'         => Chapter::factory(),
            'is_completed'       => false,
            'best_quiz_score_pct' => null,
            'completed_at'       => null,
            'last_read_at'       => now(),
        ];
    }

    public function completed(float $score = 100.0): static
    {
        return $this->state([
            'is_completed'       => true,
            'best_quiz_score_pct' => $score,
            'completed_at'       => now(),
        ]);
    }
}
