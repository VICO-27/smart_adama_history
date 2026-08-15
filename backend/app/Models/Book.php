<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'status',
        'source_file_path',
        'source_file_type',
    ];

    // ── Scopes & Helpers ─────────────────────────────────────────────────────

    /**
     * Get the canonical Smart Adama book for learner progress tracking.
     */
    public static function canonical(): ?self
    {
        return self::where('title', 'Smart Adama: Complete Guide & Ecosystem')
            ->orWhere('title', 'Smart Adama: A Conceptual Framework')
            ->first() ?? self::first();
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }
}