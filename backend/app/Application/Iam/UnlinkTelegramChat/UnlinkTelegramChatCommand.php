<?php

declare(strict_types=1);

namespace App\Application\Iam\UnlinkTelegramChat;

final readonly class UnlinkTelegramChatCommand
{
    public function __construct(
        public string $ownerId,
        public string $chatRowId,
    ) {}
}
