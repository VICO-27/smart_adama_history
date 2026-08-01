<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition(): array
    {
        return [
            'book_id'          => Book::factory(),
            'title'            => 'Chapter: ' . fake()->sentence(3),
            'order'            => fake()->numberBetween(1, 20),
            'ingestion_status' => 'draft',
            'ingested_at'      => null,
        ];
    }

    public function ready(): static
    {
        return $this->state([
            'ingestion_status' => 'ready',
            'ingested_at'      => now(),
        ]);
    }
}
