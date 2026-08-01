<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentChunk extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'section_id',
        'chunk_text',
        'chunk_index',
        'token_count',
        'embedding_status',
        // NOTE: 'embedding' is written via raw DB statement, not mass-assignment,
        // because pgvector types aren't natively supported by Eloquent casts.
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function messageSources(): HasMany
    {
        return $this->hasMany(ChatMessageSource::class);
    }

    /**
     * Convenience: return the chapter title for citation payloads.
     */
    public function getChapterTitleAttribute(): string
    {
        return $this->section->chapter->title ?? '';
    }

    /**
     * Convenience: return the section title for citation payloads.
     */
    public function getSectionTitleAttribute(): string
    {
        return $this->section->title ?? '';
    }
}
