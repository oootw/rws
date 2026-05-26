<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tariffs\Pages;

use App\Filament\Resources\Tariffs\TariffResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Тариф — конфигурационная сущность; никаких use case'ов вокруг создания
 * не нужно. handleRecordCreation наследуется (Eloquent::save() через
 * стандартный механизм Filament).
 */
final class CreateTariff extends CreateRecord
{
    protected static string $resource = TariffResource::class;
}
