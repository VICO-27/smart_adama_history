<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'chapter_id',
        'title',
        'passing_score_pct',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'passing_score_pct' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
