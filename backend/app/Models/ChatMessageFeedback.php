<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageFeedback extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'chat_message_id',
        'user_id',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'feedback' => 'string',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
