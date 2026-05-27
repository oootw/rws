<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerSubscription;

final readonly class GetOwnerSubscriptionQuery
{
    public function __construct(public string $ownerId) {}
}
