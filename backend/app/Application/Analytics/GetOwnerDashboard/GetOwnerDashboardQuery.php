<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetOwnerDashboard;

final readonly class GetOwnerDashboardQuery
{
    public function __construct(public string $ownerId) {}
}
