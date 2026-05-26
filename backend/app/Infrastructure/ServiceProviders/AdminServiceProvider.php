<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Domain\Admin\AdminActionLogIdGenerator;
use App\Domain\Admin\AdminActionLogRepository;
use App\Infrastructure\Persistence\Eloquent\Admin\EloquentAdminActionLogRepository;
use App\Infrastructure\Persistence\Eloquent\Admin\UuidAdminActionLogIdGenerator;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        AdminActionLogRepository::class => EloquentAdminActionLogRepository::class,
        AdminActionLogIdGenerator::class => UuidAdminActionLogIdGenerator::class,
    ];
}
