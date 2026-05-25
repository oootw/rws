<?php

declare(strict_types=1);

namespace App\Application\Iam\IssueOwnerImpersonationToken;

use DateTimeImmutable;

/**
 * Plain-text токен виден один раз — потом только хеш в БД.
 * Админка показывает значение в модалке "скопируйте".
 */
final readonly class IssueOwnerImpersonationTokenResult
{
    public function __construct(
        public string $plainTextToken,
        public DateTimeImmutable $expiresAt,
    ) {}
}
