<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerPushSubscriptionIdGenerator
{
    public function next(): OwnerPushSubscriptionId;
}
