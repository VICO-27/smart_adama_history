<?php

namespace Database\Factories;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatSessionFactory extends Factory
{
    protected $model = ChatSession::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'title'            => 'New Chat',
            'last_activity_at' => now(),
        ];
    }
}
