<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payments;

use App\Domain\Payments\PaymentTransactionId;
use App\Domain\Payments\PaymentTransactionIdGenerator;
use Illuminate\Support\Str;

final class UuidPaymentTransactionIdGenerator implements PaymentTransactionIdGenerator
{
    public function next(): PaymentTransactionId
    {
        return new PaymentTransactionId((string) Str::uuid());
    }
}
