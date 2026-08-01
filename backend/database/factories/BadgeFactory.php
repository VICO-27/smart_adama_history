<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'code'        => Str::slug(fake()->unique()->words(2, true)),
            'name'        => fake()->words(2, true),
            'description' => fake()->sentence(),
            'icon'        => '🏅',
            'criteria'    => ['type' => 'chapter_count', 'threshold' => 1],
        ];
    }
}
