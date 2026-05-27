<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Application\Payments\AcquirerGateway;
use App\Application\Payments\ListOwnerPayments\OwnerPaymentsReader;
use App\Application\Payments\PaymentNotificationParser;
use App\Application\Shared\Transactions\TransactionRunner;
use App\Domain\Payments\PaymentTransactionIdGenerator;
use App\Domain\Payments\PaymentTransactionRepository;
use App\Infrastructure\Payments\Tinkoff\TinkoffAcquirerGateway;
use App\Infrastructure\Payments\Tinkoff\TinkoffNotificationParser;
use App\Infrastructure\Persistence\Eloquent\Payments\EloquentOwnerPaymentsReader;
use App\Infrastructure\Persistence\Eloquent\Payments\EloquentPaymentTransactionRepository;
use App\Infrastructure\Persistence\Eloquent\Payments\UuidPaymentTransactionIdGenerator;
use App\Infrastructure\Persistence\Transactions\EloquentTransactionRunner;
use Illuminate\Support\ServiceProvider;

final class PaymentsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        PaymentTransactionRepository::class => EloquentPaymentTransactionRepository::class,
        PaymentTransactionIdGenerator::class => UuidPaymentTransactionIdGenerator::class,
        OwnerPaymentsReader::class => EloquentOwnerPaymentsReader::class,
        AcquirerGateway::class => TinkoffAcquirerGateway::class,
        PaymentNotificationParser::class => TinkoffNotificationParser::class,
        TransactionRunner::class => EloquentTransactionRunner::class,
    ];
}
