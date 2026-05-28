<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Aggregate root: одноразовый short-lived токен для привязки группового
 * Telegram-чата к владельцу через deep-link `?startgroup=<token>`.
 *
 * Жизненный цикл повторяет {@see OwnerLoginRequest}:
 *  - issue(): owner-панель просит токен → handler сохраняет.
 *  - consume(): бот, увидев /start <token> в группе, забирает токен.
 *  - повторный consume или истечение TTL → доменные исключения.
 */
final class OwnerChatLinkToken
{
    /** URL-safe hex, выдаётся из CSPRNG (см. IssueTelegramChatLinkTokenHandler). */
    private const TOKEN_PATTERN = '/^[a-f0-9]{16,128}$/';

    private function __construct(
        public readonly OwnerChatLinkTokenId $id,
        public readonly OwnerId $ownerId,
        public readonly string $token,
        public readonly DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $consumedAt,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function issue(
        OwnerChatLinkTokenId $id,
        OwnerId $ownerId,
        string $token,
        DateTimeImmutable $now,
        int $ttlSeconds,
    ): self {
        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            throw new InvalidArgumentException('Chat-link token must be a hex string of 16..128 chars.');
        }

        if ($ttlSeconds <= 0) {
            throw new InvalidArgumentException('TTL must be positive.');
        }

        return new self(
            id: $id,
            ownerId: $ownerId,
            token: $token,
            expiresAt: $now->add(new DateInterval('PT'.$ttlSeconds.'S')),
            consumedAt: null,
            createdAt: $now,
        );
    }

    public static function restore(
        OwnerChatLinkTokenId $id,
        OwnerId $ownerId,
        string $token,
        DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $consumedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $ownerId, $token, $expiresAt, $consumedAt, $createdAt);
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
            throw new ChatLinkTokenAlreadyConsumed;
        }

        if ($this->isExpiredAt($now)) {
            throw new ChatLinkTokenExpired;
        }

        $this->consumedAt = $now;
    }
}
