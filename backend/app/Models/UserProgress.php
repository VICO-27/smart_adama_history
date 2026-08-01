<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgress extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'chapter_id',
        'is_completed',
        'best_quiz_score_pct',
        'completed_at',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed'       => 'boolean',
            'best_quiz_score_pct' => 'float',
            'completed_at'       => 'datetime',
            'last_read_at'       => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
