<?php

namespace Database\Factories;

use App\Models\ContentChunk;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentChunkFactory extends Factory
{
    protected $model = ContentChunk::class;

    public function definition(): array
    {
        return [
            'section_id'       => Section::factory(),
            'chunk_text'       => fake()->paragraphs(3, true),
            'chunk_index'      => 0,
            'token_count'      => fake()->numberBetween(400, 800),
            'embedding_status' => 'ready',
        ];
    }

    public function pending(): static
    {
        return $this->state(['embedding_status' => 'pending']);
    }
}
