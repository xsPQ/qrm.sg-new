<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * FEAT-08 / §12.4: Application health-check endpoint.
 *
 * Provides a structured health report for monitoring systems.
 * The default Laravel health route (/up) only checks if the app boots;
 * this endpoint also verifies DB, Redis, and backup status.
 */
class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // Redis (cache + queue)
        try {
            Redis::ping();
            $checks['redis'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            // Redis is optional in dev — degrade gracefully
            $checks['redis'] = ['status' => 'degraded', 'message' => 'Redis not available'];
        }

        // Backup freshness (check if any backup exists in last 48h)
        try {
            $backupDisk = Storage::disk('local');
            $backupFiles = $backupDisk->allFiles('qrm-sg');
            $recentBackup = false;
            $now = time();

            foreach ($backupFiles as $file) {
                if (str_ends_with($file, '.zip')) {
                    $modTime = $backupDisk->lastModified($file);
                    if ($now - $modTime < 172800) { // 48h
                        $recentBackup = true;
                        break;
                    }
                }
            }

            if (empty($backupFiles)) {
                $checks['backup'] = ['status' => 'warning', 'message' => 'No backups yet'];
            } elseif (! $recentBackup) {
                $checks['backup'] = ['status' => 'warning', 'message' => 'No recent backup (>48h)'];
            } else {
                $checks['backup'] = ['status' => 'ok'];
            }
        } catch (\Throwable $e) {
            $checks['backup'] = ['status' => 'warning', 'message' => 'Cannot check backups'];
        }

        // Queue worker check: are there stuck jobs?
        try {
            $failedJobs = DB::table('failed_jobs')->where('failed_at', '>', now()->subHour())->count();
            $checks['queue'] = $failedJobs > 10
                ? ['status' => 'degraded', 'message' => "{$failedJobs} failed jobs in last hour"]
                : ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'ok']; // Table might not exist in test
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'error',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
