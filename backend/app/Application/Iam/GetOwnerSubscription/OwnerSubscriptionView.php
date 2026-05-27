<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerSubscription;

use DateTimeImmutable;

/**
 * Read-model для экрана подписки в Owner-панели.
 * `placesLimit = null` — лимит не задан тарифом.
 */
final readonly class OwnerSubscriptionView
{
    public function __construct(
        public ?string $tariffId,
        public ?string $tariffTitle,
        public ?DateTimeImmutable $endsAt,
        public int $daysLeft,
        public bool $isActive,
        public int $placesUsed,
        public ?int $placesLimit,
        public int $nextChargeAmount,
    ) {}
}
