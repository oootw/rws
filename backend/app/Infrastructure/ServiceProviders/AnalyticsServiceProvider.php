<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Application\Analytics\GetWeeklyAdminSummary\AdminWeeklySummaryReader;
use App\Application\Analytics\GetWeeklySummary\WeeklySummaryReader;
use App\Domain\Analytics\ActionLogIdGenerator;
use App\Domain\Analytics\ActionLogRepository;
use App\Infrastructure\Persistence\Eloquent\Analytics\EloquentActionLogRepository;
use App\Infrastructure\Persistence\Eloquent\Analytics\EloquentAdminWeeklySummaryReader;
use App\Infrastructure\Persistence\Eloquent\Analytics\EloquentWeeklySummaryReader;
use App\Infrastructure\Persistence\Eloquent\Analytics\UuidActionLogIdGenerator;
use Illuminate\Support\ServiceProvider;

final class AnalyticsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ActionLogRepository::class => EloquentActionLogRepository::class,
        ActionLogIdGenerator::class => UuidActionLogIdGenerator::class,
        WeeklySummaryReader::class => EloquentWeeklySummaryReader::class,
        AdminWeeklySummaryReader::class => EloquentAdminWeeklySummaryReader::class,
    ];
}
