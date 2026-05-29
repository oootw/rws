<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerChatLinkTokenId;
use App\Domain\Iam\OwnerChatLinkTokenIdGenerator;
use Illuminate\Support\Str;

final class UuidOwnerChatLinkTokenIdGenerator implements OwnerChatLinkTokenIdGenerator
{
    public function next(): OwnerChatLinkTokenId
    {
        return new OwnerChatLinkTokenId((string) Str::uuid());
    }
}
