<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerTelegramChatIdGenerator
{
    public function next(): OwnerTelegramChatId;
}
