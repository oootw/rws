<?php

declare(strict_types=1);

namespace App\Application\Payments\InitSubscriptionPayment;

use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountHandler;
use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountQuery;
use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdQuery;
use App\Application\Payments\AcquirerGateway;
use App\Application\Payments\InitPaymentRequest;
use App\Domain\Iam\Owner;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TariffRepository;
use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentTransaction;
use App\Domain\Payments\PaymentTransactionIdGenerator;
use App\Domain\Payments\PaymentTransactionRepository;
use Throwable;

/**
 * Создаёт транзакцию + запрашивает у эквайера ссылку на оплату.
 *
 * Никаких HTTP-кодов и payload'ов не отдаёт наверх: результат — либо URL,
 * либо текстовая причина отказа (готовая показать пользователю).
 */
final readonly class InitSubscriptionPaymentHandler
{
    public function __construct(
        private GetOwnerByIdHandler $getOwner,
        private CalculateSubscriptionAmountHandler $calculateAmount,
        private TariffRepository $tariffs,
        private PaymentTransactionRepository $transactions,
        private PaymentTransactionIdGenerator $ids,
        private AcquirerGateway $acquirer,
    ) {}

    public function handle(InitSubscriptionPaymentCommand $command): InitSubscriptionPaymentResult
    {
        if (! $this->acquirer->isConfigured()) {
            return InitSubscriptionPaymentResult::failure('Оплата временно недоступна. Обратитесь в поддержку.');
        }

        $owner = $this->getOwner->handle(new GetOwnerByIdQuery($command->ownerId));

        if ($owner === null) {
            return InitSubscriptionPaymentResult::failure('Профиль не найден. Обратитесь в поддержку.');
        }

        $tariff = $this->resolveTariff($owner);

        if ($tariff === null) {
            return InitSubscriptionPaymentResult::failure('Тариф не найден. Обратитесь в поддержку.');
        }

        $amount = new Money($this->calculateAmount->handle(
            new CalculateSubscriptionAmountQuery(ownerId: $owner->id->value),
        ));

        $transaction = PaymentTransaction::start(
            id: $this->ids->next(),
            ownerId: $owner->id,
            tariffId: $tariff->id,
            amount: $amount,
        );

        $this->transactions->save($transaction);

        try {
            $response = $this->acquirer->initSubscriptionPayment(new InitPaymentRequest(
                transactionId: $transaction->id,
                customerKey: $owner->id->value,
                amount: $amount,
                description: 'Подписка Guard Reviews',
            ));
        } catch (Throwable) {
            $transaction->fail();
            $this->transactions->save($transaction);

            return InitSubscriptionPaymentResult::failure('Не удалось создать платёж. Попробуйте позже.');
        }

        if (! $response->isSuccessful()) {
            $transaction->fail();
            $this->transactions->save($transaction);

            return InitSubscriptionPaymentResult::failure($response->errorMessage ?? 'Не удалось создать платёж.');
        }

        if ($response->externalId !== null) {
            $transaction->markInitialized($response->externalId);
            $this->transactions->save($transaction);
        }

        return InitSubscriptionPaymentResult::success((string) $response->paymentUrl);
    }

    private function resolveTariff(Owner $owner): ?Tariff
    {
        $tariffId = $owner->tariffId();

        if ($tariffId instanceof TariffId) {
            $tariff = $this->tariffs->findById($tariffId);

            if ($tariff !== null) {
                return $tariff;
            }
        }

        return $this->tariffs->findDefault();
    }
}
