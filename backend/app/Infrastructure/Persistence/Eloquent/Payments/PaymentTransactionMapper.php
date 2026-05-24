<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payments;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\TariffId;
use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentStatus;
use App\Domain\Payments\PaymentTransaction;
use App\Domain\Payments\PaymentTransactionId;
use App\Models\PaymentTransaction as PaymentTransactionModel;

final class PaymentTransactionMapper
{
    public function toDomain(PaymentTransactionModel $model): PaymentTransaction
    {
        return PaymentTransaction::restore(
            id: new PaymentTransactionId((string) $model->id),
            ownerId: new OwnerId((string) $model->user_id),
            tariffId: new TariffId((string) $model->tariff_id),
            amount: new Money((int) $model->amount),
            status: PaymentStatus::from((string) $model->status->value),
            externalId: $model->external_id !== null ? (string) $model->external_id : null,
        );
    }

    public function toPersistence(PaymentTransaction $transaction, PaymentTransactionModel $model): PaymentTransactionModel
    {
        $model->id = $transaction->id->value;
        $model->user_id = $transaction->ownerId->value;
        $model->tariff_id = $transaction->tariffId->value;
        $model->amount = $transaction->amount()->minorUnits;
        $model->external_id = $transaction->externalId();
        $model->status = $transaction->status();

        return $model;
    }
}
