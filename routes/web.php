<?php

use App\Http\Controllers\Account\QrCodeExportController;
use App\Http\Controllers\Billing\BillingWebController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Qr\QrCodeDetailController;
use App\Http\Controllers\Qr\QrCodeEditController;
use App\Http\Controllers\QrCodePublicResolverController;
use App\Http\Controllers\QrScanController;
use App\Livewire\BulkQrImport;
use App\Http\Middleware\AnonymousFairUse;
use App\Http\Middleware\ResolverRateLimit;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

// FEAT-08: Legal pages
Route::view('/agb', 'legal.agb')->name('agb');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/datenschutz', 'legal.datenschutz')->name('datenschutz');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/impressum', 'legal.impressum')->name('impressum');
Route::view('/imprint', 'legal.imprint')->name('imprint');

// Health check endpoint (§12.4 — monitoring)
Route::get('/health/detailed', [HealthController::class, 'check'])->name('health.detailed');

// Anonymous QR creation (FEAT-03)
Route::get('/create', App\Livewire\AnonymousCreator::class)
    ->middleware(AnonymousFairUse::class)
    ->name('qr.create-anonymous');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('creator', 'creator')
    ->middleware(['auth', 'verified'])
    ->name('qr.creator');

// QR-Code detail page (P2-T02). Owner/admin enforced via QrCodePolicy.
// Named `qr-codes.detail` (not `.show`) to avoid colliding with the
// apiResource show route in api.php, which already owns `qr-codes.show`.
Route::get('qr-codes/{qrCode}', [QrCodeDetailController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('qr-codes.detail');

// QR-Code edit page (P2-T03). Hosts the type-specific edit form. Owner/admin
// enforced via QrCodePolicy (update) inside the controller; the Livewire
// editor re-authorizes on mount and on every mutating action.
Route::get('qr-codes/{qrCode}/edit', [QrCodeEditController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->name('qr-codes.edit');

// Analytics page for a single QR code (P3-T01 / §12.6).
Route::get('qr-codes/{qrCode}/analytics', App\Livewire\QrCodeAnalytics::class)
    ->middleware(['auth', 'verified'])
    ->name('qr-codes.analytics');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Account page (Pflichtenheft §3.6.1, §3.4.1; P2-T05 / DEV-157): profile edit,
// password change, email-verification status/trigger and tariff display.
// Uses only `auth` (not `verified`) so the email-verification UI is reachable.
Route::view('account', 'account')
    ->middleware(['auth'])
    ->name('account');

Route::middleware(['auth'])->group(function () {
    Route::get('account/bulk-import', BulkQrImport::class)
        ->name('account.bulk-import');

    Route::get('account/qr-codes/export', [QrCodeExportController::class, 'export'])
        ->name('account.qr-export');

    Route::get('account/bulk-import/template', [QrCodeExportController::class, 'template'])
        ->name('account.bulk-import.template');

    Route::get('account/api-tokens', App\Livewire\ApiTokenManager::class)
        ->name('account.api-tokens');

    // Browser-facing billing entry points for the Account page (P2-T05). Thin web
    // glue over the P2-T09 actions (DEV-161); only the Stripe webhook (DEV-162)
    // changes the account plan. CSRF-protected POST forms. Names are suffixed
    // `.web` to avoid colliding with the API routes in api.php.
    Route::post('billing/checkout/{plan}', [BillingWebController::class, 'checkout'])
        ->name('billing.web.checkout');
    Route::post('billing/portal', [BillingWebController::class, 'portal'])
        ->name('billing.web.portal');

    // Premium alias one-time purchase (FEAT-10 §12.12)
    Route::post('billing/premium-alias/checkout', [\App\Http\Controllers\Billing\PremiumAliasCheckoutController::class, 'checkout'])
        ->name('billing.premium-alias.checkout');
    Route::get('billing/premium-alias/{purchase}/success', [\App\Http\Controllers\Billing\PremiumAliasCheckoutController::class, 'success'])
        ->name('billing.premium-alias.success');
    Route::get('billing/premium-alias/{purchase}/cancel', [\App\Http\Controllers\Billing\PremiumAliasCheckoutController::class, 'cancel'])
        ->name('billing.premium-alias.cancel');
});

require __DIR__.'/auth.php';

// Public QR-code scan surface (Pflichtenheft §3.1.2 / §3.2). The POST endpoint
// is the ONLY path that verifies a scan password (sets a signed grant cookie);
// the GET resolver never accepts a query-string password (DEV-613 F1/F2).
Route::get('/r/{code}', [QrScanController::class, 'resolve'])->name('qr.resolve');
Route::post('/r/{code}/password', [QrScanController::class, 'password'])->name('qr.scan.password');

// WiFi connect helpers: iOS .mobileconfig profile + WIFI: URI data
Route::get('/r/{code}/wifi.mobileconfig', [App\Http\Controllers\WifiConnectController::class, 'appleProfile'])
    ->name('qr.wifi.apple');
Route::get('/r/{code}/wifi-uri', [App\Http\Controllers\WifiConnectController::class, 'wifiUri'])
    ->name('qr.wifi.uri');

// Catch-all public resolver: a short code or alias served from the app host
// root. Registered LAST so every explicit route (auth, dashboard, billing,
// Filament /admin, etc.) is matched ahead of it.
// FEAT-08: Rate-limited per IP to prevent scraping/DoS.
Route::get('/{codeOrAlias}', [QrCodePublicResolverController::class, 'resolve'])
    ->middleware(ResolverRateLimit::class)
    ->name('qr.public.resolve')
    ->where('codeOrAlias', '[A-Za-z0-9][A-Za-z0-9\-]*');
