<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetOwnerDashboard;

/**
 * Сводка для виджетов главной страницы Owner-панели. Все цифры — за
 * последние 7 календарных дней по часовому поясу сервера.
 *
 * dailySeries — список ровно из 7 элементов (старый → новый), готовый
 * к отрисовке sparkline без дополнительных пересчётов на фронте.
 */
final readonly class OwnerDashboardView
{
    /**
     * @param  list<DailyMetric>  $dailySeries
     */
    public function __construct(
        public int $scans,
        public int $reviews,
        public int $negative,
        public int $redirects,
        public int $placesCount,
        public array $dailySeries,
    ) {}
}
