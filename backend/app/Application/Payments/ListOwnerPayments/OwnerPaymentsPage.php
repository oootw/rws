<?php

declare(strict_types=1);

namespace App\Application\Payments\ListOwnerPayments;

final readonly class OwnerPaymentsPage
{
    /**
     * @param  list<OwnerPaymentView>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        if ($this->perPage <= 0) {
            return 1;
        }

        return (int) max(1, ceil($this->total / $this->perPage));
    }
}
