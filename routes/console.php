<?php

use App\Jobs\AggregateScanStatsDaily;
use App\Jobs\AggregateScanStatsHourly;
use App\Models\QrCode;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Free-Tier lifecycle scheduling (Pflichtenheft §2.1, §2.4).
 *
 * Two daily jobs drive the server-managed Free expiry and the pre-expiry
 * warning hook that P2-T12's expiry-warning email subscribes to:
 *
 *  1. Transition active codes whose immutable 30-day expires_at has passed to
 *     the "expired" status, so the resolver stops serving them and they drop
 *     out of the active Free-Tier limit count.
 *  2. Dispatch the QrCodeExpiringSoon event for every active Free code that has
 *     entered the 3-day pre-expiry warning window. This is the extensible hook
 *     the expiry-warning email (P2-T12) listens to; listeners own their own
 *     idempotency. Only Free-entitlement codes are dispatched (grandfathered
 *     Pro/Business codes excluded, §3.5.3).
 *
 * Cleanup runs first so codes that already expired today do not also fire an
 * expiring-soon event in the same run.
 */
Schedule::call(fn () => QrCode::cleanupExpired())
    ->dailyAt('00:05')
    ->name('qr-codes:cleanup-expired')
    ->withoutOverlapping();

Schedule::call(fn () => QrCode::dispatchExpiringSoonEvents())
    ->dailyAt('00:10')
    ->name('qr-codes:dispatch-expiring-soon')
    ->withoutOverlapping();

/*
 * Scan analytics aggregation scheduling (Pflichtenheft §3.3.2, P3-T02).
 *
 * Two jobs recompute bounded time windows from the raw scans table and upsert
 * the result, so each run overwrites a window instead of accumulating — making
 * the jobs idempotent and safe to re-run for backfills:
 *
 *  1. Hourly, five minutes past each hour, recompute the just-completed hour
 *     into scan_stats_hourly (scan_count, unique_ips).
 *  2. Daily, after the lifecycle jobs, recompute the previous day into
 *     scan_stats_daily (scan_count, unique_ips, top_country, top_device,
 *     top_os, top_browser).
 *
 * The closures resolve the window at run time (not boot) so a long-running
 * scheduler process always aggregates the correct hour/day, and they dispatch
 * queued jobs so the worker — not the scheduler — does the DB work.
 */
Schedule::call(fn () => AggregateScanStatsHourly::dispatch(now()->startOfHour()->subHour()))
    ->hourlyAt(5)
    ->name('analytics:aggregate-hourly')
    ->withoutOverlapping();

Schedule::call(fn () => AggregateScanStatsDaily::dispatch(now()->startOfDay()->subDay()))
    ->dailyAt('00:15')
    ->name('analytics:aggregate-daily')
    ->withoutOverlapping();

/*
 * Raw-scan retention purge (Pflichtenheft §3.3.4, P3-T03).
 *
 * Deletes raw scans whose retention deadline has passed: Free-entitlement
 * scans 60 days after the QR code was created, Pro/Business scans 24 months
 * after they were recorded. The job is idempotent — only rows past their
 * delete_after are removed, so a repeat run is a no-op — and preserves the
 * aggregated statistics tables (scan_stats_hourly / scan_stats_daily).
 *
 * Ordering: it MUST run after the scan aggregation (P3-T02) so a scan is
 * never purged before it has been rolled up. It is therefore scheduled late
 * in the daily batch, after the expiry lifecycle jobs above.
 */
Schedule::command('scans:purge-expired')
    ->dailyAt('02:00')
    ->name('scans:purge-expired')
    ->withoutOverlapping();

/*
 * Guest QR-code cleanup (FEAT-03, M5-T03b).
 *
 * Deletes anonymous guest QR codes whose 24-hour validity has expired.
 * Runs hourly to keep the guest surface clean. Idempotent: only codes
 * past their expires_at with no registered owner are removed.
 */
Schedule::command('guest-qr-codes:cleanup')
    ->hourlyAt(10)
    ->name('guest-qr-codes:cleanup')
    ->withoutOverlapping();

/*
 * Daily encrypted database backup (Pflichtenheft §3.4, §12.4).
 * Uses spatie/laravel-backup: dumps PostgreSQL + app files to local disk.
 * Retention: 30 days (configured in config/backup.php → cleanup).
 * Monitoring: health-check alerts if no backup in last 24h.
 */
Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
