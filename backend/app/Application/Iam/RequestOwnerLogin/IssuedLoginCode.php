<?php

declare(strict_types=1);

namespace App\Application\Iam\RequestOwnerLogin;

use App\Domain\Iam\SubdomainSlug;
use DateTimeImmutable;

final readonly class IssuedLoginCode
{
    public function __construct(
        public string $code,
        public DateTimeImmutable $expiresAt,
        public SubdomainSlug $subdomain,
    ) {}
}
