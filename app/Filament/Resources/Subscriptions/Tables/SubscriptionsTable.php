<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Kunde')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.stripe_id')
                    ->label('Stripe-Kunde')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('stripe_id')
                    ->label('Subscription-ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('stripe_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active', 'trialing' => 'success',
                        'past_due', 'unpaid' => 'danger',
                        'canceled', 'incomplete_expired' => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('stripe_price')
                    ->label('Price-ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('ends_at')
                    ->label('Endet am')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                //
            ]);
    }
}
