<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only users with role=admin through.
 * Logs every denied attempt (Req 1.8, 16.6).
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            Log::warning('Admin route access denied', [
                'user_id' => $user?->id,
                'ip'      => $request->ip(),
                'route'   => $request->path(),
                'method'  => $request->method(),
            ]);

            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this resource.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
