<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\OwnerTelegramChatIdGenerator;
use Illuminate\Support\Str;

final class UuidOwnerTelegramChatIdGenerator implements OwnerTelegramChatIdGenerator
{
    public function next(): OwnerTelegramChatId
    {
        return new OwnerTelegramChatId((string) Str::uuid());
    }
}
