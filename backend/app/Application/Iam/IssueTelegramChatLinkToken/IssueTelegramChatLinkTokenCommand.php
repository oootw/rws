<?php

declare(strict_types=1);

namespace App\Application\Iam\IssueTelegramChatLinkToken;

final readonly class IssueTelegramChatLinkTokenCommand
{
    public function __construct(public string $ownerId) {}
}
