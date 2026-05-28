<?php

declare(strict_types=1);

use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Payments\AcquirerGateway;
use App\Application\Payments\InitPaymentResponse;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentCommand;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentHandler;
use App\Domain\Iam\Email;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\Subscription;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TariffRepository;
use App\Domain\Iam\TelegramId;
use App\Domain\Payments\PaymentStatus;
use Illuminate\Config\Repository as ConfigRepository;

function initSubscriptionPaymentHandler(
    ?AcquirerGateway $acquirer = null,
    ?TariffRepository $tariffs = null,
    ?OwnerRepository $owners = null,
): InitSubscriptionPaymentHandler {
    $owner = sampleOwner();
    $owners ??= fakeOwnerRepository([$owner]);
    $tariffs ??= fakeTariffRepository(defaultTariff(), [defaultTariff()]);
    $config = new ConfigRepository([
        'guardreviews.subscription.base_price' => 99000,
        'guardreviews.subscription.extra_place_price' => 10000,
    ]);

    return new InitSubscriptionPaymentHandler(
        new GetOwnerByIdHandler($owners),
        new CalculateSubscriptionAmountHandler($owners, $tariffs, fakePlacesRepository(), $config),
        $tariffs,
        fakePaymentTransactionRepository([]),
        fakePaymentTransactionIdGenerator(),
        $acquirer ?? fakeAcquirerGateway(),
    );
}

it('возвращает ошибку если тариф не найден', function (): void {
    $owner = Owner::restore(
        id: new OwnerId('22222222-2222-2222-2222-222222222222'),
        name: 'Иван',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug('cafe'),
        telegramId: new TelegramId('1001'),
        maxId: null,
        tariffId: new TariffId('00000000-0000-0000-0000-000000000000'),
        subscription: Subscription::none(),
    );

    $handler = initSubscriptionPaymentHandler(
        acquirer: fakeAcquirerGateway(),
        tariffs: fakeTariffRepository(null, []),
        owners: fakeOwnerRepository([$owner]),
    );

    $result = $handler->handle(new InitSubscriptionPaymentCommand(
        ownerId: $owner->id->value,
    ));

    expect($result->paymentUrl)->toBeNull()
        ->and($result->errorMessage)->toContain('Тариф не найден');
});

it('возвращает ошибку при исключении эквайера', function (): void {
    $handler = initSubscriptionPaymentHandler(
        acquirer: fakeAcquirerGateway(throws: new RuntimeException('network')),
    );

    $result = $handler->handle(new InitSubscriptionPaymentCommand(
        ownerId: sampleOwner()->id->value,
    ));

    expect($result->paymentUrl)->toBeNull()
        ->and($result->errorMessage)->toContain('Не удалось создать платёж');
});

it('помечает транзакцию неуспешной при отказе эквайера', function (): void {
    $repo = fakePaymentTransactionRepository([]);
    $config = new ConfigRepository([
        'guardreviews.subscription.base_price' => 99000,
        'guardreviews.subscription.extra_place_price' => 10000,
    ]);
    $owners = fakeOwnerRepository([sampleOwner()]);
    $tariffs = fakeTariffRepository(defaultTariff(), [defaultTariff()]);
    $handler = new InitSubscriptionPaymentHandler(
        new GetOwnerByIdHandler($owners),
        new CalculateSubscriptionAmountHandler($owners, $tariffs, fakePlacesRepository(), $config),
        $tariffs,
        $repo,
        fakePaymentTransactionIdGenerator(),
        fakeAcquirerGateway(response: InitPaymentResponse::failure('Отказ банка')),
    );

    $result = $handler->handle(new InitSubscriptionPaymentCommand(
        ownerId: sampleOwner()->id->value,
    ));

    expect($result->paymentUrl)->toBeNull()
        ->and($result->errorMessage)->toBe('Отказ банка')
        ->and($repo->transactions[0]->status())->toBe(PaymentStatus::Failed);
});

it('сохраняет внешний id при успешной инициализации', function (): void {
    $repo = fakePaymentTransactionRepository([]);
    $config = new ConfigRepository([
        'guardreviews.subscription.base_price' => 99000,
        'guardreviews.subscription.extra_place_price' => 10000,
    ]);
    $owners = fakeOwnerRepository([sampleOwner()]);
    $tariffs = fakeTariffRepository(defaultTariff(), [defaultTariff()]);
    $handler = new InitSubscriptionPaymentHandler(
        new GetOwnerByIdHandler($owners),
        new CalculateSubscriptionAmountHandler($owners, $tariffs, fakePlacesRepository(), $config),
        $tariffs,
        $repo,
        fakePaymentTransactionIdGenerator(),
        fakeAcquirerGateway(response: InitPaymentResponse::success('https://pay.test/session', '888')),
    );

    $result = $handler->handle(new InitSubscriptionPaymentCommand(
        ownerId: sampleOwner()->id->value,
    ));

    expect($result->paymentUrl)->toBe('https://pay.test/session')
        ->and($repo->transactions[0]->externalId())->toBe('888')
        ->and($repo->transactions[0]->status())->toBe(PaymentStatus::Pending);
});
