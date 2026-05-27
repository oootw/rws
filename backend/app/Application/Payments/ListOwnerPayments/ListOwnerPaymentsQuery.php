<?php

declare(strict_types=1);

namespace App\Application\Payments\ListOwnerPayments;

final readonly class ListOwnerPaymentsQuery
{
    public function __construct(
        public string $ownerId,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
