<?php

declare(strict_types=1);

use App\Application\Payments\NotificationOutcome;
use App\Application\Payments\PaymentNotification;
use App\Application\Payments\PaymentNotificationParser;
use App\Application\Payments\AcquirerGateway;
use App\Application\Payments\InitPaymentRequest;
use App\Application\Payments\InitPaymentResponse;
use App\Application\Shared\Transactions\TransactionRunner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\TariffId;
use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentTransaction;
use App\Domain\Payments\PaymentTransactionId;
use App\Domain\Payments\PaymentTransactionIdGenerator;
use App\Domain\Payments\PaymentTransactionRepository;

/**
 * @param  list<PaymentTransaction>  $transactions
 */
function fakePaymentTransactionRepository(array $transactions = []): PaymentTransactionRepository
{
    return new class($transactions) implements PaymentTransactionRepository
    {
        /** @var list<PaymentTransaction> */
        public array $transactions;

        /** @param  list<PaymentTransaction>  $transactions */
        public function __construct(array $transactions)
        {
            $this->transactions = $transactions;
        }

        public function save(PaymentTransaction $transaction): void
        {
            foreach ($this->transactions as $index => $stored) {
                if ($stored->id->value === $transaction->id->value) {
                    $this->transactions[$index] = $transaction;

                    return;
                }
            }

            $this->transactions[] = $transaction;
        }

        public function findById(PaymentTransactionId $id): ?PaymentTransaction
        {
            foreach ($this->transactions as $transaction) {
                if ($transaction->id->value === $id->value) {
                    return $transaction;
                }
            }

            return null;
        }
    };
}

function fakePaymentNotificationParser(PaymentNotification $notification): PaymentNotificationParser
{
    return new class($notification) implements PaymentNotificationParser
    {
        public function __construct(private PaymentNotification $notification) {}

        public function parse(array $payload): PaymentNotification
        {
            return $this->notification;
        }
    };
}

function immediateTransactionRunner(): TransactionRunner
{
    return new class implements TransactionRunner
    {
        public function run(callable $callback): mixed
        {
            return $callback();
        }
    };
}

function paymentNotification(
    string $transactionId,
    NotificationOutcome $outcome,
    int $amountMinorUnits = 99000,
    ?string $externalId = '123456',
): PaymentNotification {
    return new PaymentNotification(
        transactionId: $transactionId,
        outcome: $outcome,
        amountMinorUnits: $amountMinorUnits,
        externalId: $externalId,
    );
}

function pendingPaymentTransaction(
    string $id = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
    string $ownerId = '22222222-2222-2222-2222-222222222222',
): PaymentTransaction {
    return PaymentTransaction::start(
        id: new PaymentTransactionId($id),
        ownerId: new OwnerId($ownerId),
        tariffId: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        amount: new Money(99000),
    );
}

function fakePaymentTransactionIdGenerator(string $value = 'dddddddd-dddd-dddd-dddd-dddddddddddd'): PaymentTransactionIdGenerator
{
    return new class($value) implements PaymentTransactionIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): PaymentTransactionId
        {
            return new PaymentTransactionId($this->value);
        }
    };
}

function fakeAcquirerGateway(
    bool $configured = true,
    ?InitPaymentResponse $response = null,
    ?Throwable $throws = null,
): AcquirerGateway {
    return new class($configured, $response, $throws) implements AcquirerGateway
    {
        public function __construct(
            private bool $configured,
            private ?InitPaymentResponse $response,
            private ?Throwable $throws,
        ) {}

        public function isConfigured(): bool
        {
            return $this->configured;
        }

        public function initSubscriptionPayment(InitPaymentRequest $request): InitPaymentResponse
        {
            if ($this->throws !== null) {
                throw $this->throws;
            }

            return $this->response ?? InitPaymentResponse::success('https://pay.test/session', '999');
        }
    };
}
