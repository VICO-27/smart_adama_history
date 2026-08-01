<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'criteria',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
