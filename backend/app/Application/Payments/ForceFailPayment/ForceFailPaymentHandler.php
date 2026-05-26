<?php

declare(strict_types=1);

namespace App\Application\Payments\ForceFailPayment;

use App\Application\Payments\Exceptions\PaymentTransactionNotFound;
use App\Domain\Payments\PaymentTransactionId;
use App\Domain\Payments\PaymentTransactionRepository;

/**
 * Use case: админ вручную помечает зависшую транзакцию как failed.
 *
 * Доменное правило fail() запрещает понижать успешный платёж — оно
 * остаётся в силе: попытка фейлить Success бросит LogicException.
 * Идемпотентность: повторный вызов на Failed — no-op (статус уже тот).
 */
final readonly class ForceFailPaymentHandler
{
    public function __construct(
        private PaymentTransactionRepository $transactions,
    ) {}

    public function handle(ForceFailPaymentCommand $command): void
    {
        $id = new PaymentTransactionId($command->transactionId);
        $transaction = $this->transactions->findById($id);

        if ($transaction === null) {
            throw PaymentTransactionNotFound::withId($command->transactionId);
        }

        $transaction->fail();
        $this->transactions->save($transaction);
    }
}
