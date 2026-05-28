<?php

declare(strict_types=1);

namespace App\Application\Iam\IssueTelegramChatLinkToken;

use DateTimeImmutable;

final readonly class IssuedChatLinkToken
{
    public function __construct(
        public string $deepLink,
        public DateTimeImmutable $expiresAt,
    ) {}
}
