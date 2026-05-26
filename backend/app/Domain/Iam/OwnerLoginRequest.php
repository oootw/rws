<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Aggregate root: одноразовый magic-code для входа владельца через Telegram.
 *
 * Жизненный цикл:
 *  - issue(): бот по команде /login создаёт запрос с 6-значным кодом и TTL.
 *  - consume(): SPA обменивает code на сессию — code помечается consumed.
 *  - повторный consume или истечение TTL → доменные исключения.
 */
final class OwnerLoginRequest
{
    private const CODE_PATTERN = '/^\d{6}$/';

    private function __construct(
        public readonly OwnerLoginRequestId $id,
        public readonly OwnerId $ownerId,
        public readonly TelegramId $telegramId,
        public readonly string $code,
        public readonly DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $consumedAt,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function issue(
        OwnerLoginRequestId $id,
        OwnerId $ownerId,
        TelegramId $telegramId,
        string $code,
        DateTimeImmutable $now,
        int $ttlSeconds,
    ): self {
        if (preg_match(self::CODE_PATTERN, $code) !== 1) {
            throw new InvalidArgumentException('Login code must be exactly 6 digits.');
        }

        if ($ttlSeconds <= 0) {
            throw new InvalidArgumentException('TTL must be positive.');
        }

        return new self(
            id: $id,
            ownerId: $ownerId,
            telegramId: $telegramId,
            code: $code,
            expiresAt: $now->add(new DateInterval('PT'.$ttlSeconds.'S')),
            consumedAt: null,
            createdAt: $now,
        );
    }

    public static function restore(
        OwnerLoginRequestId $id,
        OwnerId $ownerId,
        TelegramId $telegramId,
        string $code,
        DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $consumedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $ownerId, $telegramId, $code, $expiresAt, $consumedAt, $createdAt);
    }

    public function consumedAt(): ?DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function isConsumed(): bool
    {
        return $this->consumedAt !== null;
    }

    public function isExpiredAt(DateTimeImmutable $moment): bool
    {
        return $moment >= $this->expiresAt;
    }

    public function consume(DateTimeImmutable $now): void
    {
        if ($this->isConsumed()) {
            throw new LoginCodeAlreadyConsumed;
        }

        if ($this->isExpiredAt($now)) {
            throw new LoginCodeExpired;
        }

        $this->consumedAt = $now;
    }
}
