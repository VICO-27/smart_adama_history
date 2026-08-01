<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageSource extends Model
{
    use HasUuids;

    protected $fillable = [
        'chat_message_id',
        'content_chunk_id',
        'similarity_score',
    ];

    protected function casts(): array
    {
        return [
            'similarity_score' => 'float',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(ContentChunk::class, 'content_chunk_id');
    }
}
