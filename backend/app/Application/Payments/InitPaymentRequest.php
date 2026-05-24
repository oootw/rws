<?php

declare(strict_types=1);

namespace App\Application\Payments;

use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentTransactionId;

final readonly class InitPaymentRequest
{
    public function __construct(
        public PaymentTransactionId $transactionId,
        public string $customerKey,
        public Money $amount,
        public string $description,
    ) {}
}
