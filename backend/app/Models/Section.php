<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'chapter_id',
        'section_number',
        'title',
        'order',
        'raw_text',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function contentChunks(): HasMany
    {
        return $this->hasMany(ContentChunk::class)->orderBy('chunk_index');
    }
}
