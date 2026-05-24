<?php

declare(strict_types=1);

namespace App\Application\Notifications\ConfirmSubscriptionRenewed;

use App\Domain\Notifications\OwnerContact;
use DateTimeImmutable;

final readonly class ConfirmSubscriptionRenewedCommand
{
    public function __construct(
        public OwnerContact $contact,
        public ?DateTimeImmutable $newExpiresAt,
    ) {}
}
