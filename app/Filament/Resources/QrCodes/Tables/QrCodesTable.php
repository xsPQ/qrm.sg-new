<?php

namespace App\Filament\Resources\QrCodes\Tables;

use App\Enums\QrStatus;
use App\Models\QrCode;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class QrCodesTable
{
    public static function configure(Table $table): Table
    {
        $statusOptions = array_combine(
            QrStatus::values(),
            array_map(fn (QrStatus $s) => $s->label(), QrStatus::cases()),
        );

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): ?string => QrStatus::tryFrom($state)?->label())
                    ->color(fn ($state): ?string => QrStatus::tryFrom($state)?->color())
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Besitzer')
                    ->sortable(),
                TextColumn::make('scan_count')
                    ->label('Scans')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Ablauf')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options($statusOptions),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('deactivate')
                    ->label('Deaktivieren')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('QR-Code deaktivieren')
                    ->modalDescription('Der QR-Code wird deaktiviert und vom Resolver nicht mehr ausgeliefert. Nutzerdaten werden nicht gelöscht. Die Aktion wird protokolliert.')
                    ->visible(fn (QrCode $record): bool => $record->status !== QrStatus::Disabled->value)
                    ->action(function (QrCode $record): void {
                        $record->setStatus(QrStatus::Disabled, 'qr.deactivate');
                    }),

                Action::make('reactivate')
                    ->label('Reaktivieren')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('QR-Code reaktivieren')
                    ->modalDescription('Der QR-Code wird wieder aktiviert. Die Aktion wird protokolliert.')
                    ->visible(fn (QrCode $record): bool => $record->status === QrStatus::Disabled->value)
                    ->action(function (QrCode $record): void {
                        $record->setStatus(QrStatus::Active, 'qr.reactivate');
                    }),
            ])
            ->toolbarActions([
                ActionGroup::make([
                    Action::make('deactivateBulk')
                        ->label('Auswahl deaktivieren')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Alle ausgewählten aktiven QR-Codes werden deaktiviert und protokolliert.')
                        ->action(function (Collection $records): void {
                            $records->each(function (QrCode $record): void {
                                if ($record->status === QrStatus::Disabled->value) {
                                    return;
                                }
                                $record->setStatus(QrStatus::Disabled, 'qr.deactivate');
                            });
                        }),
                ]),
            ]);
    }
}
