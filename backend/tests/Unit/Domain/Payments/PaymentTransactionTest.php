<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\TariffId;
use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentAmountMismatch;
use App\Domain\Payments\PaymentStatus;
use App\Domain\Payments\PaymentTransaction;
use App\Domain\Payments\PaymentTransactionId;

function newPaymentTransaction(int $amount = 99000): PaymentTransaction
{
    return PaymentTransaction::start(
        id: new PaymentTransactionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: new OwnerId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
        tariffId: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        amount: new Money($amount),
    );
}

it('создаётся в статусе «ожидание»', function (): void {
    expect(newPaymentTransaction()->status())->toBe(PaymentStatus::Pending);
});

it('подтверждается при совпадающей сумме', function (): void {
    $tx = newPaymentTransaction(99000);

    $tx->confirm(new Money(99000), externalId: '999');

    expect($tx->status())->toBe(PaymentStatus::Success)
        ->and($tx->externalId())->toBe('999')
        ->and($tx->isFinalized())->toBeTrue();
});

it('бросает «несовпадение суммы» при расхождении суммы', function (): void {
    newPaymentTransaction(99000)->confirm(new Money(50000));
})->throws(PaymentAmountMismatch::class);

it('помечается как неуспешная', function (): void {
    $tx = newPaymentTransaction();

    $tx->fail();

    expect($tx->status())->toBe(PaymentStatus::Failed)
        ->and($tx->isFinalized())->toBeFalse();
});

it('не даёт перевести успешную транзакцию в неуспешную', function (): void {
    $tx = newPaymentTransaction();
    $tx->confirm(new Money(99000));

    $tx->fail();
})->throws(LogicException::class);

it('сумма не допускает неположительное значение', function (): void {
    new Money(0);
})->throws(InvalidArgumentException::class);
