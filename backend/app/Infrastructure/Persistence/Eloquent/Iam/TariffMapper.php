<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;
use App\Models\Tariff as TariffModel;

final class TariffMapper
{
    public function toDomain(TariffModel $model): Tariff
    {
        return new Tariff(
            id: new TariffId((string) $model->id),
            title: (string) $model->title,
            basePrice: (int) $model->price,
            extraPlacePrice: (int) $model->extra_place_price,
            isDefault: (bool) $model->is_default,
            placesLimit: $model->places_limit === null ? null : (int) $model->places_limit,
        );
    }
}
