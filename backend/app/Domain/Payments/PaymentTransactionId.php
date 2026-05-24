<?php

declare(strict_types=1);

namespace App\Domain\Payments;

use InvalidArgumentException;

final readonly class PaymentTransactionId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('PaymentTransactionId не может быть пустым.');
        }
    }
}
