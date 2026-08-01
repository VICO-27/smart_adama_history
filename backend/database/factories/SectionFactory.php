<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'title'      => fake()->sentence(3),
            'order'      => fake()->numberBetween(1, 10),
            'raw_text'   => fake()->paragraphs(5, true),
        ];
    }
}
