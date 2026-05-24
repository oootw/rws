<?php

declare(strict_types=1);

namespace App\Domain\Payments;

interface PaymentTransactionRepository
{
    public function save(PaymentTransaction $transaction): void;

    public function findById(PaymentTransactionId $id): ?PaymentTransaction;
}
