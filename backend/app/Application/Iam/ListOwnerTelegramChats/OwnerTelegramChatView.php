<?php

declare(strict_types=1);

namespace App\Application\Iam\ListOwnerTelegramChats;

use DateTimeImmutable;

final readonly class OwnerTelegramChatView
{
    public function __construct(
        public string $id,
        public string $chatId,
        public ?string $title,
        public DateTimeImmutable $linkedAt,
    ) {}
}
