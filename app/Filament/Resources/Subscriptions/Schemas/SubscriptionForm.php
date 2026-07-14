<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\AdminActionLog;
use App\Models\Subscription;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kunde')
                    ->description('Kundenkonto und Stripe-Kunden-ID (schreibgeschützt – Stripe ist die Quelle der Wahrheit).')
                    ->schema([
                        Placeholder::make('user.name')
                            ->label('Kunde')
                            ->content(fn ($record): ?string => $record?->user?->name ?? '—'),
                        Placeholder::make('user.email')
                            ->label('E-Mail')
                            ->content(fn ($record): ?string => $record?->user?->email ?? '—'),
                        Placeholder::make('stripe_customer_id')
                            ->label('Stripe-Kunden-ID')
                            ->content(fn ($record): ?string => $record?->user?->stripe_id ?? '—'),
                    ])
                    ->columns(3),

                Section::make('Abonnement')
                    ->description('Stripe-Subscription-Daten (schreibgeschützt). Änderungen erfolgen ausschließlich über Stripe bzw. den Webhook P2-T10.')
                    ->schema([
                        Placeholder::make('stripe_id')
                            ->label('Subscription-ID (Stripe)')
                            ->content(fn ($record): ?string => $record?->stripe_id ?? '—'),
                        Placeholder::make('stripe_status')
                            ->label('Subscription-Status')
                            ->content(fn ($record): ?string => $record?->stripe_status ?? '—'),
                        Placeholder::make('stripe_price')
                            ->label('Price-ID')
                            ->content(fn ($record): ?string => $record?->stripe_price ?? '—'),
                        Placeholder::make('quantity')
                            ->label('Menge')
                            ->content(fn ($record): string => (string) ($record?->quantity ?? 1)),
                        Placeholder::make('trial_ends_at')
                            ->label('Testphase endet')
                            ->content(fn ($record): ?string => $record?->trial_ends_at?->toDateTimeString() ?? '—'),
                        Placeholder::make('ends_at')
                            ->label('Endet am')
                            ->content(fn ($record): ?string => $record?->ends_at?->toDateTimeString() ?? '—'),
                    ])
                    ->columns(3),

                Section::make('Letztes Event')
                    ->schema([
                        Placeholder::make('last_event')
                            ->label('Letztes protokolliertes Event')
                            ->content(function ($record): string {
                                if (! $record instanceof Subscription) {
                                    return 'Kein Event protokolliert.';
                                }

                                $last = AdminActionLog::query()
                                    ->where('target_type', Subscription::class)
                                    ->where('target_id', $record->id)
                                    ->latest('id')
                                    ->first();

                                if (! $last) {
                                    return 'Kein Event protokolliert.';
                                }

                                return "{$last->action} – {$last->created_at?->toDateTimeString()}";
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
