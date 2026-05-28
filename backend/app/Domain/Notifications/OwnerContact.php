<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

/**
 * Контактные каналы владельца, по которым его можно уведомить.
 * Любое поле канала может быть null (например, у владельца нет Telegram,
 * но есть e-mail). ownerId — для трассировки в журналах доставки;
 * nullable, чтобы поддерживать случаи без идентифицируемого владельца.
 *
 * pushSubscriptions — список активных Web Push устройств владельца.
 * telegramChatIds — групповые Telegram-чаты владельца (общий чат, эпик A).
 */
final readonly class OwnerContact
{
    /**
     * @param  list<PushSubscriptionView>  $pushSubscriptions
     * @param  list<string>  $telegramChatIds
     */
    public function __construct(
        public ?string $telegramId,
        public ?string $maxId,
        public ?string $email,
        public ?string $ownerId = null,
        public array $pushSubscriptions = [],
        public array $telegramChatIds = [],
    ) {}

    public function hasAnyChannel(): bool
    {
        return $this->hasAnyTelegramTarget()
            || $this->maxId !== null
            || $this->email !== null
            || $this->hasPushSubscriptions();
    }

    public function hasAnyTelegramTarget(): bool
    {
        return $this->telegramId !== null || $this->telegramChatIds !== [];
    }

    public function hasPushSubscriptions(): bool
    {
        return $this->pushSubscriptions !== [];
    }
}
