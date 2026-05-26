<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerLoginRequest;
use App\Domain\Iam\OwnerLoginRequestId;
use App\Domain\Iam\OwnerLoginRequestRepository;
use App\Domain\Shared\Clock\Clock;
use App\Models\OwnerLoginRequest as OwnerLoginRequestModel;

final readonly class EloquentOwnerLoginRequestRepository implements OwnerLoginRequestRepository
{
    public function __construct(
        private OwnerLoginRequestMapper $mapper,
        private Clock $clock,
    ) {}

    public function save(OwnerLoginRequest $request): void
    {
        $model = OwnerLoginRequestModel::query()->find($request->id->value) ?? new OwnerLoginRequestModel;

        $this->mapper->toPersistence($request, $model)->save();
    }

    public function findActiveByCode(string $code): ?OwnerLoginRequest
    {
        $model = OwnerLoginRequestModel::query()
            ->where('code', $code)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', $this->clock->now())
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findById(OwnerLoginRequestId $id): ?OwnerLoginRequest
    {
        $model = OwnerLoginRequestModel::query()->find($id->value);

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
