<?php

declare(strict_types=1);

namespace App\Application\Notifications\RemindAboutSubscriptionExpiry;

use App\Domain\Notifications\OwnerContact;
use DateTimeImmutable;

final readonly class RemindAboutSubscriptionExpiryCommand
{
    public function __construct(
        public OwnerContact $contact,
        public DateTimeImmutable $expiresAt,
        public int $daysBefore,
    ) {}
}
