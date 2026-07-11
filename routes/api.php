<?php

use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Billing\BillingCheckoutController;
use App\Http\Controllers\Billing\BillingPortalController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Qr\QrCodeDownloadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/qr/{code}/download.svg', [QrCodeDownloadController::class, 'downloadSvg'])
    ->name('qr.download.svg');
Route::get('/qr/{code}/download.png', [QrCodeDownloadController::class, 'downloadPng'])
    ->name('qr.download.png');

// Stripe webhook (Pflichtenheft §3.5.1, P2-T10 / DEV-162): public, protected by
// the Stripe signature. Registered outside the auth group on purpose.
Route::post('billing/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('billing.webhook');

Route::middleware('auth:sanctum')->group(function () {
    // Registered before the resource so it is matched ahead of the {qr_code}
    // show route (Pflichtenheft §2.4: Upgrade-Prompt data for the Creator/Dashboard).
    Route::get('qr-codes/free-tier-status', [QrCodeController::class, 'freeTierStatus'])
        ->name('qr-codes.free-tier-status');

    // Feature-gate flags for the Creator/Edit UI (P2-T07): plan-derived
    // capability flags + upgrade hint.
    Route::get('qr-codes/feature-status', [QrCodeController::class, 'featureStatus'])
        ->name('qr-codes.feature-status');

    Route::apiResource('qr-codes', QrCodeController::class)->only([
        'index', 'store', 'show', 'update', 'destroy',
    ]);

    // Billing / Stripe (Pflichtenheft §3.5.1–§3.5.2, P2-T09 / DEV-161).
    Route::post('billing/checkout', BillingCheckoutController::class)
        ->name('billing.checkout');
    Route::post('billing/portal', BillingPortalController::class)
        ->name('billing.portal');
});
