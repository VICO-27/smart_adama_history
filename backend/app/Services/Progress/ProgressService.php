<?php

namespace App\Services\Progress;

use App\Models\Chapter;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Cache;

/**
 * Manages per-user chapter progress records.
 * Requirements 10.1 – 10.3
 *
 * Full implementation here — the stub comment in tasks.md T14 refers to
 * the badge/analytics integration, not the core progress logic.
 */
class ProgressService
{
    /**
     * Record that the user has read (visited) this chapter.
     * Does not mark it as completed — completion requires passing the quiz.
     */
    public function markChapterRead(User $user, Chapter $chapter): UserProgress
    {
        $progress = UserProgress::firstOrCreate(
            ['user_id' => $user->id, 'chapter_id' => $chapter->id],
            ['is_completed' => false]
        );

        $progress->update(['last_read_at' => now()]);

        $this->invalidateDashboardCache($user->id);

        return $progress->fresh();
    }

    /**
     * Mark a chapter as completed (called after passing its quiz — Req 10.1).
     */
    public function markChapterComplete(User $user, Chapter $chapter, float $scorePct): UserProgress
    {
        $progress = UserProgress::firstOrCreate(
            ['user_id' => $user->id, 'chapter_id' => $chapter->id],
            ['is_completed' => false]
        );

        // Track best quiz score across retakes (Req 9.4)
        $bestScore = max($scorePct, $progress->best_quiz_score_pct ?? 0);

        $progress->update([
            'is_completed'       => true,
            'best_quiz_score_pct' => $bestScore,
            'completed_at'       => $progress->completed_at ?? now(),
            'last_read_at'       => now(),
        ]);

        $this->invalidateDashboardCache($user->id);

        return $progress->fresh();
    }

    /**
     * Compute aggregated progress summary for a user.
     * Returns overall completion %, chapters done, avg quiz score (Req 10.2).
     */
    public function getSummary(User $user): array
    {
        $cacheKey = "progress_summary:{$user->id}";

        return Cache::remember($cacheKey, 60, function () use ($user) {
            $totalChapters     = Chapter::count();
            $progressRecords   = UserProgress::where('user_id', $user->id)->get();
            $completedChapters = $progressRecords->where('is_completed', true)->count();
            $avgScore          = $progressRecords
                ->whereNotNull('best_quiz_score_pct')
                ->avg('best_quiz_score_pct');

            return [
                'total_chapters'     => $totalChapters,
                'completed_chapters' => $completedChapters,
                'completion_pct'     => $totalChapters > 0
                    ? round(($completedChapters / $totalChapters) * 100, 1)
                    : 0,
                'average_quiz_score' => $avgScore ? round($avgScore, 1) : null,
            ];
        });
    }

    /**
     * Invalidate the dashboard cache when progress changes (Req 12.3).
     */
    public function invalidateDashboardCache(string $userId): void
    {
        Cache::forget("progress_summary:{$userId}");
        Cache::forget("dashboard:{$userId}");
    }
}
