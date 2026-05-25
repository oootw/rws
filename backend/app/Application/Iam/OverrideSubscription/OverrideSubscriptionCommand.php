<?php

declare(strict_types=1);

namespace App\Application\Iam\OverrideSubscription;

use DateTimeImmutable;

/**
 * Ручной override подписки админом.
 * endsAt = null — сбросить (стало "никогда не оформлялась / истекла").
 */
final readonly class OverrideSubscriptionCommand
{
    public function __construct(
        public string $ownerId,
        public ?DateTimeImmutable $endsAt,
    ) {}
}
