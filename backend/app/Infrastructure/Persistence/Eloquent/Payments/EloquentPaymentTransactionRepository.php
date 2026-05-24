<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payments;

use App\Domain\Payments\PaymentTransaction;
use App\Domain\Payments\PaymentTransactionId;
use App\Domain\Payments\PaymentTransactionRepository;
use App\Models\PaymentTransaction as PaymentTransactionModel;

final readonly class EloquentPaymentTransactionRepository implements PaymentTransactionRepository
{
    public function __construct(
        private PaymentTransactionMapper $mapper,
    ) {}

    public function save(PaymentTransaction $transaction): void
    {
        $model = PaymentTransactionModel::query()->find($transaction->id->value)
            ?? new PaymentTransactionModel;

        $this->mapper->toPersistence($transaction, $model)->save();
    }

    public function findById(PaymentTransactionId $id): ?PaymentTransaction
    {
        $model = PaymentTransactionModel::query()->find($id->value);

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
