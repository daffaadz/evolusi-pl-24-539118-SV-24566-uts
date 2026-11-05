<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Deep healthcheck endpoint inspecting database and cache status.
     * Returns HTTP 200 when all services are healthy, HTTP 503 if any service fails.
     */
    public function check(Request $request): JsonResponse
    {
        $isHealthy = true;
        $services = [];

        // 1. Verify Database Connection and Query Execution
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            $services['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection established and query executed successfully',
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $services['database'] = [
                'status' => 'unhealthy',
                'error' => 'Database connection failed: ' . $e->getMessage(),
            ];

            Log::error('[HEALTH] [DATABASE] [FAILED] Database connection failed', [
                'who' => 'system_healthcheck',
                'action' => 'database_connection_check',
                'reason' => $e->getMessage(),
            ]);
        }

        // 2. Verify Cache Store Read / Write / Delete Operations
        try {
            $testKey = 'health_ping_' . microtime(true);
            Cache::put($testKey, 'ok', 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved !== 'ok') {
                throw new \Exception('Cache read/write verification mismatch');
            }

            $services['cache'] = [
                'status' => 'healthy',
                'message' => 'Cache driver read and write operations functioning normally',
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $services['cache'] = [
                'status' => 'unhealthy',
                'error' => 'Cache operation failed: ' . $e->getMessage(),
            ];

            Log::error('[HEALTH] [CACHE] [FAILED] Cache check failed', [
                'who' => 'system_healthcheck',
                'action' => 'cache_check',
                'reason' => $e->getMessage(),
            ]);
        }

        $statusCode = $isHealthy ? 200 : 503;

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ], $statusCode);
    }
}

