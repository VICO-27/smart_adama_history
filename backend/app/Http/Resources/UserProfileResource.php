<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Extended user resource including aggregate progress summary (Req 2.1).
 */
class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Aggregate progress — computed lazily to avoid N+1
        $progressRecords   = $this->progress()->with('chapter')->get();
        $completedChapters = $progressRecords->where('is_completed', true)->count();
        $totalChapters     = \App\Models\Chapter::count();
        $avgScore          = $progressRecords->whereNotNull('best_quiz_score_pct')
            ->avg('best_quiz_score_pct');

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'role'           => $this->role,
            'avatar_url'     => $this->avatar_url,
            'locale'         => $this->locale,
            'notify_badges'  => $this->notify_badges,
            'created_at'     => $this->created_at?->toISOString(),
            'progress_summary' => [
                'completed_chapters' => $completedChapters,
                'total_chapters'     => $totalChapters,
                'completion_pct'     => $totalChapters > 0
                    ? round(($completedChapters / $totalChapters) * 100, 1)
                    : 0,
                'average_quiz_score' => $avgScore ? round($avgScore, 1) : null,
            ],
        ];
    }
}
