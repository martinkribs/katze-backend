<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class HealthController extends BaseController
{
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'socket' => $this->checkSocket(),
                'meilisearch' => $this->checkMeilisearch(),
            ]
        ];

        // If any service is not healthy, set overall status to error
        foreach ($health['services'] as $service) {
            if ($service['status'] === 'error') {
                $health['status'] = 'error';
                break;
            }
        }

        return response()->json($health);
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return [
                'status' => 'ok',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => 'ok',
                'message' => 'Cache is working properly'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $key = 'health_check_' . time() . '.txt';

            // Try to write and read
            $disk->put($key, 'health check');
            $content = $disk->get($key);
            $disk->delete($key);

            // Check disk space
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpacePercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            return [
                'status' => 'ok',
                'message' => 'Storage is working properly',
                'used_space_percentage' => round($usedSpacePercentage, 2) . '%'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkSocket(): array
    {
        try {
            // Check if socket server is running on default port
            $connection = @fsockopen(
                env('PUSHER_HOST', '127.0.0.1'),
                (int) env('PUSHER_PORT', 6001), // Cast port to integer
                $errno,
                $errstr,
                5
            );

            if ($connection) {
                fclose($connection);
                return [
                    'status' => 'ok',
                    'message' => 'Socket server is running'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Socket server is not running',
                'error' => "Connection failed: $errno $errstr"
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Socket check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkMeilisearch(): array
    {
        try {
            $host = config('scout.meilisearch.host');
            $response = Http::get("$host/health");

            if ($response->successful() && $response->json('status') === 'available') {
                return [
                    'status' => 'ok',
                    'message' => 'Meilisearch is running and healthy'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Meilisearch is not healthy',
                'error' => 'Service reported unhealthy status'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Meilisearch check failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
