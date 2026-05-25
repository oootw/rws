<?php

declare(strict_types=1);

namespace App\Application\Iam\IssueOwnerImpersonationToken;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Shared\Clock\Clock;

/**
 * Use case: админ выпускает токен имперсонации владельца.
 *
 * Что делает:
 *  1) Проверяет, что владелец существует (TenantNotFound иначе).
 *  2) Через порт OwnerImpersonationTokenIssuer создаёт PAT
 *     с ability "impersonated" и сроком жизни ttlMinutes.
 *
 * Безопасность:
 *  - plain-text возвращается ровно один раз;
 *  - срок жизни — по умолчанию 15 минут;
 *  - ability "impersonated" позволит ЛК/PWA отличить такую сессию
 *    и, например, запретить смену пароля.
 */
final readonly class IssueOwnerImpersonationTokenHandler
{
    private const DEFAULT_TTL_MINUTES = 15;

    public function __construct(
        private OwnerRepository $owners,
        private OwnerImpersonationTokenIssuer $issuer,
        private Clock $clock,
    ) {}

    public function handle(IssueOwnerImpersonationTokenCommand $command): IssueOwnerImpersonationTokenResult
    {
        $ownerId = new OwnerId($command->ownerId);

        if ($this->owners->findById($ownerId) === null) {
            throw new TenantNotFound;
        }

        $ttl = $command->ttlMinutes > 0 ? $command->ttlMinutes : self::DEFAULT_TTL_MINUTES;
        $expiresAt = $this->clock->now()->modify("+{$ttl} minutes");

        $issued = $this->issuer->issue($ownerId, $expiresAt);

        return new IssueOwnerImpersonationTokenResult(
            plainTextToken: $issued['plain_text'],
            expiresAt: $issued['expires_at'],
        );
    }
}
