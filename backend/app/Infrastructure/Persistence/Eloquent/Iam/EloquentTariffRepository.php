<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TariffRepository;
use App\Models\Tariff as TariffModel;

final readonly class EloquentTariffRepository implements TariffRepository
{
    private const DEFAULT_TITLE = 'MVP';

    public function __construct(
        private TariffMapper $mapper,
    ) {}

    public function findById(TariffId $id): ?Tariff
    {
        $model = TariffModel::query()->find($id->value);

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findDefault(): ?Tariff
    {
        $model = TariffModel::query()->where('title', self::DEFAULT_TITLE)->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
