<?php

declare(strict_types=1);

namespace App\Application\Payments\ListOwnerPayments;

use App\Domain\Iam\OwnerId;

interface OwnerPaymentsReader
{
    public function paginate(OwnerId $ownerId, int $page, int $perPage): OwnerPaymentsPage;
}
