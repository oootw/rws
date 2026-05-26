<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

/**
 * Контактные каналы владельца, по которым его можно уведомить.
 * Любое поле канала может быть null (например, у владельца нет Telegram,
 * но есть e-mail). ownerId — для трассировки в журналах доставки;
 * nullable, чтобы поддерживать случаи без идентифицируемого владельца.
 */
final readonly class OwnerContact
{
    public function __construct(
        public ?string $telegramId,
        public ?string $maxId,
        public ?string $email,
        public ?string $ownerId = null,
    ) {}

    public function hasAnyChannel(): bool
    {
        return $this->telegramId !== null
            || $this->maxId !== null
            || $this->email !== null;
    }
}
