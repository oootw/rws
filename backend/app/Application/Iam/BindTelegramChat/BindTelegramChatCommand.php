<?php

declare(strict_types=1);

namespace App\Application\Iam\BindTelegramChat;

final readonly class BindTelegramChatCommand
{
    public function __construct(
        public string $token,
        public string $chatId,
        public ?string $title,
    ) {}
}
