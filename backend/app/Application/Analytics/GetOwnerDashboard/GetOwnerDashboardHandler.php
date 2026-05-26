<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetOwnerDashboard;

use App\Domain\Iam\OwnerId;
use App\Domain\Shared\Clock\Clock;

final readonly class GetOwnerDashboardHandler
{
    public function __construct(
        private OwnerDashboardReader $reader,
        private Clock $clock,
    ) {}

    public function handle(GetOwnerDashboardQuery $query): OwnerDashboardView
    {
        return $this->reader->forOwner(new OwnerId($query->ownerId), $this->clock->now());
    }
}
