<?php

declare(strict_types=1);

namespace App\Application\Iam\ListOwnerTelegramChats;

final readonly class ListOwnerTelegramChatsQuery
{
    public function __construct(public string $ownerId) {}
}
