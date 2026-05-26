<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetOwnerDashboard;

final readonly class DailyMetric
{
    public function __construct(
        public string $date, // YYYY-MM-DD
        public int $scans,
        public int $reviews,
    ) {}
}
