<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Application\Iam\IssueOwnerImpersonationToken\OwnerImpersonationTokenIssuer;
use App\Domain\Iam\OwnerIdGenerator;
use App\Domain\Iam\OwnerLoginRequestIdGenerator;
use App\Domain\Iam\OwnerLoginRequestRepository;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\TariffRepository;
use App\Infrastructure\Iam\SanctumOwnerImpersonationTokenIssuer;
use App\Infrastructure\Persistence\Eloquent\Iam\EloquentOwnerLoginRequestRepository;
use App\Infrastructure\Persistence\Eloquent\Iam\EloquentOwnerRepository;
use App\Infrastructure\Persistence\Eloquent\Iam\EloquentTariffRepository;
use App\Infrastructure\Persistence\Eloquent\Iam\UuidOwnerIdGenerator;
use App\Infrastructure\Persistence\Eloquent\Iam\UuidOwnerLoginRequestIdGenerator;
use Illuminate\Support\ServiceProvider;
use Random\Randomizer;

final class IamServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        OwnerRepository::class => EloquentOwnerRepository::class,
        OwnerIdGenerator::class => UuidOwnerIdGenerator::class,
        OwnerLoginRequestRepository::class => EloquentOwnerLoginRequestRepository::class,
        OwnerLoginRequestIdGenerator::class => UuidOwnerLoginRequestIdGenerator::class,
        TariffRepository::class => EloquentTariffRepository::class,
        OwnerImpersonationTokenIssuer::class => SanctumOwnerImpersonationTokenIssuer::class,
    ];

    public function register(): void
    {
        $this->app->bind(Randomizer::class, static fn () => new Randomizer);
    }
}
