<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Web Push подписка одного устройства одного владельца.
 *
 * Жизненный цикл:
 *  - register(): SPA сообщает endpoint + публичные ключи браузера,
 *    repository upsert'ит по endpoint (см. RegisterPushSubscriptionHandler).
 *  - touchLastSeen(): обновляется при повторной регистрации тем же владельцем.
 *  - изменение владельца у уже известного endpoint — через repository (set ownerId).
 */
final class OwnerPushSubscription
{
    private const MAX_KEY_LENGTH = 255;

    private const MAX_USER_AGENT_LENGTH = 255;

    private function __construct(
        public readonly OwnerPushSubscriptionId $id,
        public readonly OwnerId $ownerId,
        public readonly PushSubscriptionEndpoint $endpoint,
        public readonly string $p256dh,
        public readonly string $auth,
        public readonly ?string $userAgent,
        public readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $lastSeenAt,
    ) {}

    public static function register(
        OwnerPushSubscriptionId $id,
        OwnerId $ownerId,
        PushSubscriptionEndpoint $endpoint,
        string $p256dh,
        string $auth,
        ?string $userAgent,
        DateTimeImmutable $now,
    ): self {
        self::assertKey('p256dh', $p256dh);
        self::assertKey('auth', $auth);
        self::assertUserAgent($userAgent);

        return new self(
            id: $id,
            ownerId: $ownerId,
            endpoint: $endpoint,
            p256dh: $p256dh,
            auth: $auth,
            userAgent: $userAgent,
            createdAt: $now,
            lastSeenAt: $now,
        );
    }

    public static function restore(
        OwnerPushSubscriptionId $id,
        OwnerId $ownerId,
        PushSubscriptionEndpoint $endpoint,
        string $p256dh,
        string $auth,
        ?string $userAgent,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $lastSeenAt,
    ): self {
        return new self($id, $ownerId, $endpoint, $p256dh, $auth, $userAgent, $createdAt, $lastSeenAt);
    }

    public function lastSeenAt(): ?DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function touchLastSeen(DateTimeImmutable $now): void
    {
        $this->lastSeenAt = $now;
    }

    private static function assertKey(string $name, string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException("Push subscription {$name} не может быть пустым.");
        }

        if (mb_strlen($value) > self::MAX_KEY_LENGTH) {
            throw new InvalidArgumentException("Push subscription {$name} не должен превышать ".self::MAX_KEY_LENGTH.' символов.');
        }
    }

    private static function assertUserAgent(?string $userAgent): void
    {
        if ($userAgent !== null && mb_strlen($userAgent) > self::MAX_USER_AGENT_LENGTH) {
            throw new InvalidArgumentException('User-Agent не должен превышать '.self::MAX_USER_AGENT_LENGTH.' символов.');
        }
    }
}
