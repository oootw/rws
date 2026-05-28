<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\OwnerPushSubscriptionIdGenerator;
use Illuminate\Support\Str;

final class UuidOwnerPushSubscriptionIdGenerator implements OwnerPushSubscriptionIdGenerator
{
    public function next(): OwnerPushSubscriptionId
    {
        return new OwnerPushSubscriptionId((string) Str::uuid());
    }
}
