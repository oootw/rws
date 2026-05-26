<?php

declare(strict_types=1);

namespace App\Application\Analytics\GetOwnerDashboard;

use App\Domain\Iam\OwnerId;
use DateTimeImmutable;

/**
 * Read-model порт: считает KPI для конкретного владельца за период.
 * Конкретный SQL — в инфраструктурном адаптере, чтобы не тащить Eloquent в Application.
 */
interface OwnerDashboardReader
{
    public function forOwner(OwnerId $ownerId, DateTimeImmutable $until): OwnerDashboardView;
}
