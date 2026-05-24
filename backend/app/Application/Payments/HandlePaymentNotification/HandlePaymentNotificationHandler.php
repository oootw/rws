<?php

declare(strict_types=1);

namespace App\Application\Payments\HandlePaymentNotification;

use App\Application\Iam\ExtendSubscription\ExtendSubscriptionCommand;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionHandler;
use App\Application\Notifications\ConfirmSubscriptionRenewed\ConfirmSubscriptionRenewedCommand;
use App\Application\Notifications\ConfirmSubscriptionRenewed\ConfirmSubscriptionRenewedHandler;
use App\Application\Payments\Exceptions\PaymentTransactionNotFound;
use App\Application\Payments\NotificationOutcome;
use App\Application\Payments\PaymentNotificationParser;
use App\Application\Shared\Transactions\TransactionRunner;
use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentTransactionId;
use App\Domain\Payments\PaymentTransactionRepository;

/**
 * Use case обработки уведомления от эквайера.
 *
 * Идемпотентен: повторное подтверждение уже успешной транзакции не запускает
 * продление повторно. Валидация подписи и формата — на стороне парсера
 * (InvalidPaymentNotification пробрасывается выше — webhook вернёт 400).
 */
final readonly class HandlePaymentNotificationHandler
{
    public function __construct(
        private PaymentNotificationParser $parser,
        private PaymentTransactionRepository $transactions,
        private ExtendSubscriptionHandler $extendSubscription,
        private ConfirmSubscriptionRenewedHandler $confirmRenewed,
        private TransactionRunner $tx,
    ) {}

    public function handle(HandlePaymentNotificationCommand $command): void
    {
        $notification = $this->parser->parse($command->payload);

        $transaction = $this->transactions->findById(new PaymentTransactionId($notification->transactionId));

        if ($transaction === null) {
            throw PaymentTransactionNotFound::withId($notification->transactionId);
        }

        if ($transaction->isFinalized()) {
            return;
        }

        if ($notification->outcome === NotificationOutcome::Rejected) {
            $transaction->fail();
            $this->transactions->save($transaction);

            return;
        }

        if ($notification->outcome !== NotificationOutcome::Confirmed) {
            return;
        }

        $this->tx->run(function () use ($transaction, $notification): void {
            $transaction->confirm(new Money($notification->amountMinorUnits), $notification->externalId);
            $this->transactions->save($transaction);

            $owner = $this->extendSubscription->handle(
                new ExtendSubscriptionCommand(ownerId: $transaction->ownerId->value),
            );

            $this->confirmRenewed->handle(new ConfirmSubscriptionRenewedCommand(
                contact: $owner->asNotificationContact(),
                newExpiresAt: $owner->subscription()->endsAt,
            ));
        });
    }
}
