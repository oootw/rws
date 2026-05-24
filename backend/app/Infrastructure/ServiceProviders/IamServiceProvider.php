<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Domain\Iam\OwnerIdGenerator;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\TariffRepository;
use App\Infrastructure\Persistence\Eloquent\Iam\EloquentOwnerRepository;
use App\Infrastructure\Persistence\Eloquent\Iam\EloquentTariffRepository;
use App\Infrastructure\Persistence\Eloquent\Iam\UuidOwnerIdGenerator;
use Illuminate\Support\ServiceProvider;

final class IamServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        OwnerRepository::class => EloquentOwnerRepository::class,
        OwnerIdGenerator::class => UuidOwnerIdGenerator::class,
        TariffRepository::class => EloquentTariffRepository::class,
    ];
}
