<?php

namespace App\Http\Resources;

use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Safe Canonical Resolution for Testing Environments
        $canonicalBook = Book::whereIn('title', [
            'Smart Adama: Complete Guide & Ecosystem',
            'Smart Adama: A Conceptual Framework'
        ])->first();

        $canonicalChapterIds = $canonicalBook 
            ? $canonicalBook->chapters()->pluck('id') 
            : Chapter::pluck('id');
        
        $totalChapters = $canonicalChapterIds->count();

        $progressRecords = $this->progress()
            ->whereIn('chapter_id', $canonicalChapterIds)
            ->with('chapter')
            ->get();
            
        $completedChapters = $progressRecords->where('is_completed', true)->count();
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