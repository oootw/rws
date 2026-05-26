<?php

declare(strict_types=1);

namespace App\Application\Payments\ForceFailPayment;

final readonly class ForceFailPaymentCommand
{
    public function __construct(
        public string $transactionId,
    ) {}
}
