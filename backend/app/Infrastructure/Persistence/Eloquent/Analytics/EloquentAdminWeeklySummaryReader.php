<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics;

use App\Application\Analytics\GetWeeklyAdminSummary\AdminWeeklySummary;
use App\Application\Analytics\GetWeeklyAdminSummary\AdminWeeklySummaryReader;
use App\Domain\Analytics\ActionType;
use App\Models\ActionLog as ActionLogModel;
use DateTimeImmutable;

/**
 * Глобальный аналог EloquentWeeklySummaryReader: считает counts по типам
 * за весь сервис, без фильтра по точке. Используется dashboard-виджетом.
 */
final class EloquentAdminWeeklySummaryReader implements AdminWeeklySummaryReader
{
    public function between(DateTimeImmutable $since, DateTimeImmutable $until): AdminWeeklySummary
    {
        $counts = ActionLogModel::query()
            ->whereBetween('created_at', [$since, $until])
            ->selectRaw('action_type, count(*) as total')
            ->groupBy('action_type')
            ->pluck('total', 'action_type');

        return new AdminWeeklySummary(
            scanned: (int) ($counts[ActionType::Scanned->value] ?? 0),
            redirectedExternal: (int) ($counts[ActionType::RedirectedExternal->value] ?? 0),
            leftNegative: (int) ($counts[ActionType::LeftNegative->value] ?? 0),
            adminDeletedReview: (int) ($counts[ActionType::AdminDeletedReview->value] ?? 0),
        );
    }
}
