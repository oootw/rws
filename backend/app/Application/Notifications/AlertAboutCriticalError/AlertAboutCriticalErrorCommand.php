<?php

declare(strict_types=1);

namespace App\Application\Notifications\AlertAboutCriticalError;

final readonly class AlertAboutCriticalErrorCommand
{
    public function __construct(
        public string $placeId,
        public string $placeTitle,
        public string $ownerName,
        public ?string $ownerEmail,
        public ?string $ownerSubdomain,
        public string $context,
    ) {}
}
