<?php

declare(strict_types=1);

use App\Application\Payments\Exceptions\PaymentTransactionNotFound;
use App\Application\Payments\ForceFailPayment\ForceFailPaymentCommand;
use App\Application\Payments\ForceFailPayment\ForceFailPaymentHandler;
use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentStatus;
use App\Domain\Payments\PaymentTransaction;
use App\Domain\Payments\PaymentTransactionId;

it('переводит pending транзакцию в failed', function (): void {
    $transaction = pendingPaymentTransaction();
    $repo = fakePaymentTransactionRepository([$transaction]);

    (new ForceFailPaymentHandler($repo))->handle(
        new ForceFailPaymentCommand(transactionId: $transaction->id->value),
    );

    expect($repo->transactions[0]->status())->toBe(PaymentStatus::Failed);
});

it('идемпотентен: failed → failed без ошибок', function (): void {
    $failed = PaymentTransaction::restore(
        id: new PaymentTransactionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: new \App\Domain\Iam\OwnerId('22222222-2222-2222-2222-222222222222'),
        tariffId: new \App\Domain\Iam\TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        amount: new Money(99000),
        status: PaymentStatus::Failed,
        externalId: null,
    );
    $repo = fakePaymentTransactionRepository([$failed]);

    (new ForceFailPaymentHandler($repo))->handle(
        new ForceFailPaymentCommand(transactionId: $failed->id->value),
    );

    expect($repo->transactions[0]->status())->toBe(PaymentStatus::Failed);
});

it('бросает LogicException, если транзакция уже Success (доменное правило)', function (): void {
    $success = PaymentTransaction::restore(
        id: new PaymentTransactionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: new \App\Domain\Iam\OwnerId('22222222-2222-2222-2222-222222222222'),
        tariffId: new \App\Domain\Iam\TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        amount: new Money(99000),
        status: PaymentStatus::Success,
        externalId: 'ext-1',
    );

    (new ForceFailPaymentHandler(fakePaymentTransactionRepository([$success])))->handle(
        new ForceFailPaymentCommand(transactionId: $success->id->value),
    );
})->throws(LogicException::class);

it('бросает PaymentTransactionNotFound для неизвестного id', function (): void {
    (new ForceFailPaymentHandler(fakePaymentTransactionRepository()))->handle(
        new ForceFailPaymentCommand(transactionId: '00000000-0000-0000-0000-000000000000'),
    );
})->throws(PaymentTransactionNotFound::class);
