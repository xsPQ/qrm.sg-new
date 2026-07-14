<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\QrCode;
use App\Models\Scan;
use App\Models\User;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('Total QR Codes'), QrCode::count())
                ->description(__('All codes in the system'))
                ->icon('heroicon-o-qrcode')
                ->color('indigo'),

            Stat::make(__('Active Users (24h)'), User::where('updated_at', '>=', now()->subDay())->count())
                ->description(__('Users active in the last 24 hours'))
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make(__('Scans Today'), Scan::whereDate('created_at', today())->count())
                ->description(__('Total scans in the last 24h'))
                ->icon('heroicon-o-chart-bar')
                ->color('info'),

            Stat::make(__('Active Subscriptions'), Subscription::where('stripe_status', 'active')->count())
                ->description(__('Paying customers'))
                ->icon('heroicon-o-credit-card')
                ->color('warning'),
        ];
    }
}
