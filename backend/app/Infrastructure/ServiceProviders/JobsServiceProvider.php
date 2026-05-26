<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Application\Jobs\FailedJobsActions;
use App\Application\Jobs\FailedJobsReader;
use App\Infrastructure\Jobs\EloquentFailedJobsReader;
use App\Infrastructure\Jobs\LaravelFailedJobsActions;
use Illuminate\Support\ServiceProvider;

/**
 * Композиция read/write порта над таблицей failed_jobs для админ-страницы.
 */
final class JobsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        FailedJobsReader::class => EloquentFailedJobsReader::class,
        FailedJobsActions::class => LaravelFailedJobsActions::class,
    ];
}
