<?php

declare(strict_types=1);

namespace App\Application\Iam\CalculateSubscriptionAmount;

final readonly class CalculateSubscriptionAmountQuery
{
    public function __construct(public string $ownerId) {}
}
