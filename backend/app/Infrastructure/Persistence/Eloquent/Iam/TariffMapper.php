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
            isDefault: (bool) $model->is_default,
        );
    }
}
