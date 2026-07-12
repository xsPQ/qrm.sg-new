<?php

namespace App\Providers;

use App\Domain\Analytics\AllowlistGuard;
use App\Domain\Analytics\LocalGeoIpResolver;
use App\Events\FreeTierLimitReached;
use App\Events\QrCodeExpiringSoon;
use App\Listeners\SendFreeTierUpgradeHint;
use App\Listeners\SendQrCodeExpiryWarning;
use App\Models\QrCode;
use App\Observers\QrCodeRevisionObserver;
use App\Policies\QrCodePolicy;
use App\Services\QrCodeResolver;
use App\Services\ResolverCache;
use App\Services\ScanRecorder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Shared, configured GeoIP resolver (loads the MaxMind DBs once per
        // process). Bound here so the resolver, the scan recorder and the
        // cache-hit job all share one reader pair with the configured paths.
        $this->app->singleton(LocalGeoIpResolver::class, function () {
            return new LocalGeoIpResolver(
                cityDbPath: config('analytics.geoip.city_db'),
                asnDbPath: config('analytics.geoip.asn_db'),
                locales: config('analytics.geoip.locales', ['en', 'de']),
            );
        });

        $this->app->singleton(AllowlistGuard::class);
        $this->app->singleton(ScanRecorder::class);
        $this->app->singleton(ResolverCache::class);

        // Resolver shares the bound scan recorder + cache (P3-T05).
        $this->app->singleton(QrCodeResolver::class, function ($app) {
            return new QrCodeResolver(
                scanRecorder: $app->make(ScanRecorder::class),
                cache: $app->make(ResolverCache::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(QrCode::class, QrCodePolicy::class);

        // FEAT-07: Auto-capture revision snapshots on QR code edits.
        QrCode::observe(QrCodeRevisionObserver::class);

        // E-Mail-Flow hooks (P2-T12). The listeners enqueue the corresponding
        // queued Mailables, so delivery runs over the queue (P1-T06).
        Event::listen(QrCodeExpiringSoon::class, SendQrCodeExpiryWarning::class);
        Event::listen(FreeTierLimitReached::class, SendFreeTierUpgradeHint::class);
    }
}
