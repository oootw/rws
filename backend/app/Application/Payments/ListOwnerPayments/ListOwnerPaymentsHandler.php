<?php

declare(strict_types=1);

namespace App\Application\Payments\ListOwnerPayments;

use App\Domain\Iam\OwnerId;

final readonly class ListOwnerPaymentsHandler
{
    public function __construct(
        private OwnerPaymentsReader $reader,
    ) {}

    public function handle(ListOwnerPaymentsQuery $query): OwnerPaymentsPage
    {
        return $this->reader->paginate(
            ownerId: new OwnerId($query->ownerId),
            page: max(1, $query->page),
            perPage: max(1, min(100, $query->perPage)),
        );
    }
}
