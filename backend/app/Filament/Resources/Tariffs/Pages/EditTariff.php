<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tariffs\Pages;

use App\Filament\Resources\Tariffs\Actions\TariffActionFactory;
use App\Filament\Resources\Tariffs\TariffResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditTariff extends EditRecord
{
    protected static string $resource = TariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            TariffActionFactory::setDefault(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить тариф?'),
        ];
    }
}
