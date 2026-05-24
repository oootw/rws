<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetWeeklySummary;

use App\Domain\Analytics\WeeklySummary;
use App\Domain\Places\PlaceId;
use App\Domain\Shared\Clock\Clock;

final readonly class GetWeeklySummaryHandler
{
    public function __construct(
        private WeeklySummaryReader $reader,
        private Clock $clock,
    ) {}

    public function handle(GetWeeklySummaryQuery $query): WeeklySummary
    {
        return $this->reader->forPlace(new PlaceId($query->placeId), $this->clock->now());
    }
}
