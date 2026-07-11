<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserPlan;
use App\Enums\UserStatus;
use App\Models\AdminActionLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $statusOptions = array_combine(
            UserStatus::values(),
            array_map(fn (UserStatus $s) => $s->label(), UserStatus::cases()),
        );
        $planOptions = array_combine(
            UserPlan::values(),
            array_map(fn (UserPlan $p) => $p->label(), UserPlan::cases()),
        );

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan')
                    ->label('Tarif')
                    ->badge()
                    ->formatStateUsing(fn ($state): ?string => UserPlan::tryFrom($state)?->label())
                    ->color(fn ($state): ?string => UserPlan::tryFrom($state)?->color())
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): ?string => UserStatus::tryFrom($state)?->label())
                    ->color(fn ($state): ?string => UserStatus::tryFrom($state)?->color())
                    ->sortable(),
                TextColumn::make('stripe_id')
                    ->label('Stripe-ID')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options($statusOptions),
                SelectFilter::make('plan')
                    ->label('Tarif')
                    ->options($planOptions),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('suspend')
                    ->label('Sperren')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Nutzer sperren')
                    ->modalDescription('Der Nutzer wird gesperrt und kann sich nicht mehr einloggen. Die Aktion wird protokolliert.')
                    ->visible(fn (User $record): bool => $record->status !== UserStatus::Blocked->value)
                    ->action(function (User $record): void {
                        self::changeStatus($record, UserStatus::Blocked, 'user.suspend');
                    }),

                Action::make('unsuspend')
                    ->label('Entsperren')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Nutzer entsperren')
                    ->modalDescription('Der Nutzer wird wieder freigegeben. Die Aktion wird protokolliert.')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Blocked->value)
                    ->action(function (User $record): void {
                        self::changeStatus($record, UserStatus::Active, 'user.unsuspend');
                    }),

                Action::make('changePlan')
                    ->label('Plan ändern')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->modalHeading('Tarif ändern')
                    ->modalDescription('Ändert den Account-Tarif. Ressourcenbezogener Bestandsschutz (Entitlement-Snapshot) bleibt unberührt. Die Aktion wird protokolliert.')
                    ->fillForm(fn (User $record): array => ['plan' => $record->plan])
                    ->form([
                        Select::make('plan')
                            ->label('Tarif')
                            ->options($planOptions)
                            ->required(),
                    ])
                    ->action(function (array $data, User $record): void {
                        self::changePlan($record, $data['plan']);
                    }),
            ])
            ->toolbarActions([
                ActionGroup::make([
                    Action::make('suspendBulk')
                        ->label('Auswahl sperren')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(function (User $record): void {
                                if ($record->status === UserStatus::Blocked->value) {
                                    return;
                                }
                                self::changeStatus($record, UserStatus::Blocked, 'user.suspend');
                            });
                        }),
                ]),
            ]);
    }

    public static function admin(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function changeStatus(User $record, UserStatus $status, string $action): void
    {
        $before = ['status' => $record->status];
        $record->status = $status->value;
        $record->save();

        AdminActionLog::record(
            admin: self::admin() ?? $record,
            action: $action,
            target: $record,
            before: $before,
            after: ['status' => $record->status],
        );
    }

    public static function changePlan(User $record, string $plan): void
    {
        $before = ['plan' => $record->plan];
        $record->plan = $plan;
        $record->save();

        AdminActionLog::record(
            admin: self::admin() ?? $record,
            action: 'user.change_plan',
            target: $record,
            before: $before,
            after: ['plan' => $record->plan],
        );
    }
}
