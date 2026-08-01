<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
    use HasUuids;

    protected $fillable = [
        'quiz_attempt_id',
        'quiz_question_id',
        'selected_option_ids',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'is_correct'          => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
