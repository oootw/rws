<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetWeeklySummary;

final readonly class GetWeeklySummaryQuery
{
    public function __construct(public string $placeId) {}
}
