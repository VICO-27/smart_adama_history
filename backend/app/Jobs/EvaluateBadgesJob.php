<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Gamification\BadgeEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async badge evaluation triggered after any progress-affecting event (Req 11.1).
 * Runs BadgeEvaluationService for the given user and logs newly awarded badges.
 */
class EvaluateBadgesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly string $userId)
    {
    }

    public function handle(BadgeEvaluationService $service): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('EvaluateBadgesJob: user not found', ['user_id' => $this->userId]);
            return;
        }

        $awarded = $service->evaluate($user);

        if ($awarded->isNotEmpty()) {
            Log::info('EvaluateBadgesJob: awarded badges', [
                'user_id' => $this->userId,
                'badges'  => $awarded->pluck('code')->toArray(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('EvaluateBadgesJob failed permanently', [
            'user_id' => $this->userId,
            'error'   => $e->getMessage(),
        ]);
    }
}
