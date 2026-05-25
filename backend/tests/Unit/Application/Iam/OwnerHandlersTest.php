<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionCommand;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionHandler;
use App\Application\Iam\RegisterOwner\RegisterOwnerCommand;
use App\Application\Iam\RegisterOwner\RegisterOwnerHandler;
use Illuminate\Config\Repository as ConfigRepository;

it('регистрирует нового владельца с тарифом по умолчанию', function (): void {
    $owners = fakeOwnerRepository();
    $tariffs = fakeTariffRepository(defaultTariff());

    $ownerId = (new RegisterOwnerHandler(
        owners: $owners,
        idGenerator: fakeOwnerIdGenerator('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        tariffs: $tariffs,
    ))->handle(new RegisterOwnerCommand(
        name: 'Иван',
        email: 'owner@example.com',
        subdomain: 'cafe',
        telegramId: '1001',
    ));

    expect($ownerId->value)->toBe('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa')
        ->and($owners->owners)->toHaveCount(1)
        ->and($owners->owners[0]->name())->toBe('Иван')
        ->and($owners->owners[0]->subdomain()->value)->toBe('cafe')
        ->and($owners->owners[0]->telegramId()?->value)->toBe('1001');
});

it('бросает исключение «адрес уже занят», если адрес занят', function (): void {
    (new RegisterOwnerHandler(
        owners: fakeOwnerRepository(takenSubdomains: ['cafe']),
        idGenerator: fakeOwnerIdGenerator('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        tariffs: fakeTariffRepository(defaultTariff()),
    ))->handle(new RegisterOwnerCommand(
        name: 'Иван',
        email: 'owner@example.com',
        subdomain: 'cafe',
        telegramId: null,
    ));
})->throws(SubdomainAlreadyTaken::class);

it('продлевает подписку владельца на N дней', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);

    $updated = (new ExtendSubscriptionHandler(
        owners: $owners,
        clock: frozenClockAt('2026-06-01T00:00:00Z'),
        config: new ConfigRepository(['guardreviews.subscription.duration_days' => 30]),
    ))->handle(new ExtendSubscriptionCommand(
        ownerId: $owner->id->value,
        durationDays: 14,
    ));

    expect($updated->subscription()->endsAt?->format('Y-m-d'))->toBe('2026-06-15')
        ->and($owners->owners[0]->subscription()->endsAt?->format('Y-m-d'))->toBe('2026-06-15');
});

it('бросает исключение «арендатор не найден» для неизвестного владельца', function (): void {
    (new ExtendSubscriptionHandler(
        owners: fakeOwnerRepository(),
        clock: frozenClockAt('2026-06-01T00:00:00Z'),
        config: new ConfigRepository,
    ))->handle(new ExtendSubscriptionCommand(ownerId: '00000000-0000-0000-0000-000000000000'));
})->throws(TenantNotFound::class);
