<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title'            => fake()->sentence(4),
            'status'           => 'draft',
            'source_file_path' => null,
            'source_file_type' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }
}
