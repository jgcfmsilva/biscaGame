<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\JsonResponse;

class SystemStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $status = [
            'api' => 'online',
            'database' => 'offline',
            'redis' => 'offline',
            'websocket' => 'unknown',
            'queue_worker' => 'unknown',
            'server_time' => now()->toIso8601String(),
            'timestamp' => time(),
            'services' => [],
        ];

        // API (this endpoint is responding)
        $status['services']['api'] = [
            'status' => 'online',
            'latency_ms' => 1,
            'details' => 'Laravel API',
        ];

        // Check Database
        $databaseStatus = $this->checkDatabase();
        $status['database'] = $databaseStatus['status'];
        if (isset($databaseStatus['error'])) {
            $status['database_error'] = $databaseStatus['error'];
        }
        $status['services']['database'] = $databaseStatus;

        // Check Redis
        $redisStatus = $this->checkRedis();
        $status['redis'] = $redisStatus['status'];
        if (isset($redisStatus['error'])) {
            $status['redis_error'] = $redisStatus['error'];
        }
        $status['services']['redis'] = $redisStatus;

        // Check WebSocket (TCP reachability)
        $websocketHost = env('WEBSOCKET_HOST', 'websocket');
        $websocketPort = (int) env('WEBSOCKET_PORT', 3000);
        $websocketStatus = $this->checkTcpService($websocketHost, $websocketPort, 'WebSocket');
        $status['websocket'] = $websocketStatus['status'];
        $status['services']['websocket'] = $websocketStatus;

        // Queue Worker (assume healthy when Redis is reachable, since it consumes Redis queues)
        $queueStatus = $redisStatus;
        $queueStatus['details'] = 'Queue Worker / Emails';
        $status['queue_worker'] = $queueStatus['status'];
        $status['services']['queue_worker'] = $queueStatus;

        return response()->json($status);
    }

    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = (int) round((microtime(true) - $start) * 1000) ?: 1;
            return [
                'status' => 'online',
                'latency_ms' => $latency,
                'details' => DB::getConfig('database') ?? 'database',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::connection()->ping();
            $latency = (int) round((microtime(true) - $start) * 1000) ?: 1;
            return [
                'status' => 'online',
                'latency_ms' => $latency,
                'details' => 'Redis',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkTcpService(?string $host, ?int $port, string $label = 'service'): array
    {
        if (!$host || !$port) {
            return [
                'status' => 'unknown',
                'details' => $label,
            ];
        }

        $start = microtime(true);
        $connection = @fsockopen($host, $port, $errno, $errstr, 1.5);

        if ($connection !== false) {
            fclose($connection);
            $latency = (int) round((microtime(true) - $start) * 1000) ?: 1;
            return [
                'status' => 'online',
                'latency_ms' => $latency,
                'details' => $label,
                'endpoint' => "{$host}:{$port}",
            ];
        }

        return [
            'status' => 'offline',
            'error' => $errstr ?: 'Unable to reach service',
            'details' => $label,
            'endpoint' => "{$host}:{$port}",
        ];
    }
}
