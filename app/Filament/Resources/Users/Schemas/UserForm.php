<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserPlan;
use App\Enums\UserStatus;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stammdaten')
                    ->description('Bearbeitbare Nutzerdaten.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Account-Status')
                    ->description('Plan und Status werden über dedizierte, protokollierte Aktionen geändert (Sperren/Entsperren, Plan ändern).')
                    ->schema([
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn ($record): ?string => $record?->status
                                ? UserStatus::tryFrom($record->status)?->label()
                                : null),
                        Placeholder::make('plan')
                            ->label('Tarif')
                            ->content(fn ($record): ?string => $record?->plan
                                ? UserPlan::tryFrom($record->plan)?->label()
                                : null),
                        Placeholder::make('email_verified_at')
                            ->label('E-Mail verifiziert')
                            ->content(fn ($record): ?string => $record?->email_verified_at?->toDateTimeString() ?? 'Nein'),
                    ])
                    ->columns(3),

                Section::make('Billing')
                    ->description('Schreibgeschützt – Stripe ist die Quelle der Wahrheit.')
                    ->schema([
                        Placeholder::make('stripe_id')
                            ->label('Stripe-Kunden-ID')
                            ->content(fn ($record): ?string => $record?->stripe_id ?? '—'),
                    ]),
            ]);
    }
}
