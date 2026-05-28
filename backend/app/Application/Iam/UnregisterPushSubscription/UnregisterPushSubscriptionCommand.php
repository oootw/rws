<?php

declare(strict_types=1);

namespace App\Application\Iam\UnregisterPushSubscription;

final readonly class UnregisterPushSubscriptionCommand
{
    public function __construct(
        public string $ownerId,
        public string $endpoint,
    ) {}
}
