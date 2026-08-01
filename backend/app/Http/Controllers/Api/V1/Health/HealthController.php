<?php

namespace App\Http\Controllers\Api\V1\Health;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * GET /api/v1/health
 * Public endpoint — reports DB, cache, and queue connectivity.
 * Requirement 18.5
 */
class HealthController extends Controller
{
    public function __invoke()
    {
        $checks = [];

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
        }

        // Cache (Redis)
        try {
            Cache::put('_health_check', true, 5);
            Cache::get('_health_check');
            $checks['cache'] = 'ok';
        } catch (\Throwable $e) {
            $checks['cache'] = 'fail';
        }

        // Queue (Redis)
        try {
            $size = Queue::size();
            $checks['queue'] = 'ok';
        } catch (\Throwable $e) {
            $checks['queue'] = 'fail';
        }

        $allOk = ! in_array('fail', $checks, true);

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $allOk ? 200 : 503);
    }
}
