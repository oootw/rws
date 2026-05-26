<?php

declare(strict_types=1);

namespace App\Application\Iam\CalculatePlaceCharge;

final readonly class CalculatePlaceChargeQuery
{
    public function __construct(public string $ownerId) {}
}
