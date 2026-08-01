<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-user chat rate limiter (Req 16.2).
 * Default: 20 messages per 5 minutes per user.
 * Configured via CHAT_RATE_LIMIT_PER_5_MIN in .env.
 */
class ThrottleChat
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts  = (int) config('ai.chat_rate_limit.max_attempts', 20);
        $decayMinutes = (int) config('ai.chat_rate_limit.decay_minutes', 5);
        $decaySeconds = $decayMinutes * 60;

        $key = 'chat:' . $request->user()?->id;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => [
                    'code'    => 'TOO_MANY_REQUESTS',
                    'message' => "Chat rate limit exceeded. You can send another message in {$retryAfter} seconds.",
                ],
            ], 429, ['Retry-After' => $retryAfter]);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}
