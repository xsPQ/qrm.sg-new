<?php

namespace App\Filament\Resources\QrCodes\Schemas;

use App\Enums\QrCodeType;
use App\Enums\QrStatus;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QrCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('QR-Code')
                    ->description('Schreibgeschützte Einblickansicht. Nutzerdaten werden nicht direkt bearbeitet – Änderungen erfolgen über protokollierte Aktionen (Deaktivieren/Reaktivieren).')
                    ->schema([
                        Placeholder::make('title')
                            ->label('Titel')
                            ->content(fn ($record): ?string => $record?->title),
                        Placeholder::make('type')
                            ->label('Typ')
                            ->content(fn ($record): ?string => $record?->type
                                ? QrCodeType::tryFrom($record->type)?->label()
                                : null),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn ($record): ?string => $record?->status
                                ? QrStatus::tryFrom($record->status)?->label()
                                : null),
                        Placeholder::make('owner')
                            ->label('Besitzer')
                            ->content(fn ($record): ?string => $record?->user?->name ?? '—'),
                    ])
                    ->columns(4),

                Section::make('Nutzung & Limits')
                    ->schema([
                        Placeholder::make('scan_count')
                            ->label('Scans')
                            ->content(fn ($record): string => (string) ($record?->scan_count ?? 0)),
                        Placeholder::make('max_scans')
                            ->label('Max. Scans')
                            ->content(fn ($record): string => $record?->max_scans ? (string) $record->max_scans : 'Unbegrenzt'),
                        Placeholder::make('burn')
                            ->label('Einmal-Code (Burn)')
                            ->content(fn ($record): string => $record?->burn ? 'Ja' : 'Nein'),
                        Placeholder::make('expires_at')
                            ->label('Ablaufdatum')
                            ->content(fn ($record): ?string => $record?->expires_at?->toDateTimeString() ?? 'Keiner'),
                    ])
                    ->columns(4),

                Section::make('Inhalt')
                    ->schema([
                        Placeholder::make('content')
                            ->label('Inhalt (JSON)')
                            ->content(fn ($record): ?string => $record?->content
                                ? '<pre class="overflow-x-auto rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">'.e(json_encode($record->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)).'</pre>'
                                : '—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
