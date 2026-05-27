<?php

declare(strict_types=1);

namespace App\Application\Payments\ListOwnerPayments;

use App\Domain\Payments\PaymentStatus;
use DateTimeImmutable;

final readonly class OwnerPaymentView
{
    public function __construct(
        public string $id,
        public int $amount,
        public PaymentStatus $status,
        public ?string $externalId,
        public ?string $tariffTitle,
        public DateTimeImmutable $createdAt,
    ) {}
}
