<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetWeeklyAdminSummary;

/**
 * Сводка действий по всему сервису за период (для админ-dashboard).
 * Доменная WeeklySummary считается на конкретную точку — здесь нужен
 * глобальный срез по типам, поэтому отдельный read-model DTO.
 */
final readonly class AdminWeeklySummary
{
    public function __construct(
        public int $scanned,
        public int $redirectedExternal,
        public int $leftNegative,
        public int $adminDeletedReview,
    ) {}
}
