<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetWeeklyAdminSummary;

use DateTimeImmutable;

/**
 * Read-model порт: считает глобальные счётчики action_logs за период
 * (одним SQL без восстановления записей в память).
 */
interface AdminWeeklySummaryReader
{
    public function between(DateTimeImmutable $since, DateTimeImmutable $until): AdminWeeklySummary;
}
