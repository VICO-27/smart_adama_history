<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'type',
        'explanation',
        'order',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class)->orderBy('order');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }
}
