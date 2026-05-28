<?php

declare(strict_types=1);

namespace App\Application\Iam\RegisterPushSubscription;

final readonly class RegisterPushSubscriptionCommand
{
    public function __construct(
        public string $ownerId,
        public string $endpoint,
        public string $p256dh,
        public string $auth,
        public ?string $userAgent,
    ) {}
}
