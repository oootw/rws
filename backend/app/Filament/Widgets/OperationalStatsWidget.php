<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Application\Analytics\GetWeeklyAdminSummary\AdminWeeklySummaryReader;
use App\Application\Jobs\FailedJobsReader;
use App\Domain\Payments\PaymentStatus;
use App\Models\PaymentTransaction;
use DateTimeImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard-виджет: операционные показатели за последние 7 дней —
 * скан/негатив, упавшие задачи в очереди, успешные платежи.
 *
 * Все числа считаются через порты Application-слоя (AdminWeeklySummaryReader,
 * FailedJobsReader), кроме платежей: для них хватает прямого Eloquent-count'а
 * по Status и created_at — отдельный read-model порт сейчас был бы оверкилл.
 */
final class OperationalStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 30;

    protected function getStats(): array
    {
        $until = new DateTimeImmutable;
        $since = $until->modify('-7 days');

        $summary = app(AdminWeeklySummaryReader::class)->between($since, $until);
        $failedJobs = app(FailedJobsReader::class)->count();
        $successPayments7d = PaymentTransaction::query()
            ->where('status', PaymentStatus::Success)
            ->where('created_at', '>=', $since)
            ->count();

        return [
            Stat::make('Сканов за 7 дней', (string) $summary->scanned)
                ->description('QR → форма')
                ->color('info'),

            Stat::make('Негатив за 7 дней', (string) $summary->leftNegative)
                ->description('перехваченных отзывов')
                ->color($summary->leftNegative > 0 ? 'warning' : 'success'),

            Stat::make('Упавшие задачи', (string) $failedJobs)
                ->description('в очереди failed_jobs')
                ->color($failedJobs > 0 ? 'danger' : 'success'),

            Stat::make('Платежи за 7 дней', (string) $successPayments7d)
                ->description('успешные')
                ->color('success'),
        ];
    }
}
