<?php

declare(strict_types=1);

namespace App\Application\Iam\IssueOwnerImpersonationToken;

final readonly class IssueOwnerImpersonationTokenCommand
{
    public function __construct(
        public string $ownerId,
        /** Минут до истечения токена. */
        public int $ttlMinutes = 15,
    ) {}
}
