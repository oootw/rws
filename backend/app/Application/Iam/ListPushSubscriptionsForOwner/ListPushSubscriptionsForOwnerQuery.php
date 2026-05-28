<?php

declare(strict_types=1);

namespace App\Application\Iam\ListPushSubscriptionsForOwner;

final readonly class ListPushSubscriptionsForOwnerQuery
{
    public function __construct(public string $ownerId) {}
}
