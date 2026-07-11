<?php

namespace App\Filament\Resources\QrCodes\Pages;

use App\Enums\QrStatus;
use App\Filament\Resources\QrCodes\QrCodeResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditQrCode extends EditRecord
{
    protected static string $resource = QrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deactivate')
                ->label('Deaktivieren')
                ->icon('heroicon-o-eye-slash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('QR-Code deaktivieren')
                ->modalDescription('Der QR-Code wird deaktiviert und vom Resolver nicht mehr ausgeliefert. Die Aktion wird protokolliert.')
                ->visible(fn (): bool => $this->getRecord()->status !== QrStatus::Disabled->value)
                ->action(function (): void {
                    $this->getRecord()->setStatus(QrStatus::Disabled, 'qr.deactivate');
                    $this->refreshFormData(['status']);
                }),

            Action::make('reactivate')
                ->label('Reaktivieren')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('QR-Code reaktivieren')
                ->modalDescription('Der QR-Code wird wieder aktiviert. Die Aktion wird protokolliert.')
                ->visible(fn (): bool => $this->getRecord()->status === QrStatus::Disabled->value)
                ->action(function (): void {
                    $this->getRecord()->setStatus(QrStatus::Active, 'qr.reactivate');
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
