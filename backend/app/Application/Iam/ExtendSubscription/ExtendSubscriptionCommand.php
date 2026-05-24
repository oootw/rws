<?php

declare(strict_types=1);

namespace App\Application\Iam\ExtendSubscription;

final readonly class ExtendSubscriptionCommand
{
    public function __construct(
        public string $ownerId,
        public ?int $durationDays = null,
    ) {}
}
