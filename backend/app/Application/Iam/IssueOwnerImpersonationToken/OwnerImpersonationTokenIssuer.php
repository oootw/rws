<?php

declare(strict_types=1);

namespace App\Application\Iam\IssueOwnerImpersonationToken;

use App\Domain\Iam\OwnerId;
use DateTimeImmutable;

/**
 * Порт для выпуска токена имперсонации владельца.
 *
 * Реализация (Sanctum) живёт в Infrastructure — Domain/Application
 * не должны знать о laravel/sanctum. Любой будущий API-токен-механизм
 * (JWT, opaque) подменяется одной строкой в сервис-провайдере.
 */
interface OwnerImpersonationTokenIssuer
{
    /**
     * @return array{plain_text: string, expires_at: DateTimeImmutable}
     */
    public function issue(OwnerId $ownerId, DateTimeImmutable $expiresAt): array;
}
