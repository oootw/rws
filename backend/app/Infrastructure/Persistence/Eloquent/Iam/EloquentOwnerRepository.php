<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\TelegramId;
use App\Models\User as UserModel;

final readonly class EloquentOwnerRepository implements OwnerRepository
{
    public function __construct(
        private OwnerMapper $mapper,
    ) {}

    public function save(Owner $owner): void
    {
        $model = UserModel::query()->find($owner->id->value) ?? new UserModel;

        $this->mapper->toPersistence($owner, $model)->save();
    }

    public function findById(OwnerId $id): ?Owner
    {
        return $this->loadOne(static fn () => UserModel::query()->find($id->value));
    }

    public function findBySubdomain(SubdomainSlug $subdomain): ?Owner
    {
        return $this->loadOne(
            static fn () => UserModel::query()->where('subdomain_slug', $subdomain->value)->first(),
        );
    }

    public function findByTelegramId(TelegramId $telegramId): ?Owner
    {
        return $this->loadOne(
            static fn () => UserModel::query()->where('telegram_id', $telegramId->value)->first(),
        );
    }

    public function subdomainExists(SubdomainSlug $subdomain): bool
    {
        return UserModel::query()->where('subdomain_slug', $subdomain->value)->exists();
    }

    public function delete(OwnerId $id): void
    {
        UserModel::query()->whereKey($id->value)->delete();
    }

    private function loadOne(callable $fetch): ?Owner
    {
        $model = $fetch();

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
