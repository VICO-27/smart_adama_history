<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStreak extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_date' => 'date',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
