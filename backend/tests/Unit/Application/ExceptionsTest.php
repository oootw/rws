<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Application\Payments\Exceptions\PaymentTransactionNotFound;

it('создаёт исключение «адрес уже занят» с адресом', function (): void {
    $exception = new SubdomainAlreadyTaken('cafe');

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->slug)->toBe('cafe')
        ->and($exception->getMessage())->toContain('cafe');
});

it('создаёт исключение «транзакция не найдена» с идентификатором', function (): void {
    $exception = PaymentTransactionNotFound::withId('pay-123');

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())->toContain('pay-123');
});
