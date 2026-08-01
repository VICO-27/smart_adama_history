<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Anonymizes PII for a soft-deleted user after 30 days (Req 2.4).
 * Replaces name and email with anonymous placeholders.
 */
class AnonymizeUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly string $userId)
    {
    }

    public function handle(): void
    {
        $user = User::withTrashed()->find($this->userId);

        if (! $user || ! $user->trashed()) {
            return; // User restored or not found — nothing to do
        }

        $user->forceFill([
            'name'       => 'Deleted User',
            'email'      => "deleted_{$user->id}@anonymized.invalid",
            'avatar_url' => null,
        ])->saveQuietly();

        Log::info('User PII anonymized', ['user_id' => $this->userId]);
    }
}
