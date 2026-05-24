<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

/**
 * Контактные каналы владельца, по которым его можно уведомить.
 * Любое поле может быть null — это нормально (например, у владельца
 * нет Telegram, но есть e-mail).
 */
final readonly class OwnerContact
{
    public function __construct(
        public ?string $telegramId,
        public ?string $maxId,
        public ?string $email,
    ) {}

    public function hasAnyChannel(): bool
    {
        return $this->telegramId !== null
            || $this->maxId !== null
            || $this->email !== null;
    }
}
