<?php

declare(strict_types=1);

namespace App\Application\Notifications\SendWeeklyDigest;

use App\Domain\Analytics\WeeklySummary;
use App\Domain\Notifications\OwnerContact;

final readonly class SendWeeklyDigestCommand
{
    public function __construct(
        public OwnerContact $contact,
        public string $placeTitle,
        public WeeklySummary $summary,
    ) {}
}
