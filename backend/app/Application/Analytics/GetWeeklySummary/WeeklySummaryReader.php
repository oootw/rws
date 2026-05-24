<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetWeeklySummary;

use App\Domain\Analytics\WeeklySummary;
use App\Domain\Places\PlaceId;
use DateTimeImmutable;

/**
 * Read-model порт: считает агрегаты по action_logs одним SQL без
 * восстановления доменных записей в память.
 */
interface WeeklySummaryReader
{
    public function forPlace(PlaceId $placeId, DateTimeImmutable $until): WeeklySummary;
}
