<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tariffs\Actions;

use App\Application\Iam\SetDefaultTariff\SetDefaultTariffCommand;
use App\Application\Iam\SetDefaultTariff\SetDefaultTariffHandler;
use App\Models\Tariff;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Custom-actions для TariffResource. Только то, что нельзя сделать обычным
 * Eloquent::save() — назначение default-тарифа с гарантией единственности.
 */
final class TariffActionFactory
{
    public static function setDefault(): Action
    {
        return Action::make('set_default')
            ->label('Сделать default')
            ->icon('heroicon-o-star')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Этот тариф станет дефолтным, у остальных флаг is_default сбросится.')
            ->visible(fn (Tariff $record): bool => ! $record->is_default)
            ->action(function (Tariff $record): void {
                app(SetDefaultTariffHandler::class)->handle(
                    new SetDefaultTariffCommand(tariffId: (string) $record->id),
                );

                Notification::make()
                    ->title('Тариф назначен default')
                    ->success()
                    ->send();
            });
    }
}
