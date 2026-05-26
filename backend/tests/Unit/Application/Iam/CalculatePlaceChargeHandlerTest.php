<?php

declare(strict_types=1);

use App\Application\Iam\CalculatePlaceCharge\CalculatePlaceChargeHandler;
use App\Application\Iam\CalculatePlaceCharge\CalculatePlaceChargeQuery;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\Email;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\Subscription;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TariffRepository;
use App\Domain\Iam\TelegramId;
use Illuminate\Config\Repository as ConfigRepository;

function tariffWithExtraPrice(int $extraPlacePrice): Tariff
{
    return new Tariff(
        id: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        title: 'MVP',
        basePrice: 99000,
        extraPlacePrice: $extraPlacePrice,
    );
}

function tariffRepoWith(Tariff $tariff): TariffRepository
{
    return new class($tariff) implements TariffRepository
    {
        public function __construct(private Tariff $tariff) {}

        public function findById(TariffId $id): ?Tariff
        {
            return $id->value === $this->tariff->id->value ? $this->tariff : null;
        }

        public function findDefault(): ?Tariff
        {
            return $this->tariff;
        }

        public function markAsOnlyDefault(TariffId $id): void {}
    };
}

function ownerWithSubscriptionEndingAt(?DateTimeImmutable $endsAt): Owner
{
    return Owner::restore(
        id: new OwnerId('22222222-2222-2222-2222-222222222222'),
        name: 'Иван',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug('cafe'),
        telegramId: new TelegramId('1001'),
        maxId: null,
        tariffId: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        subscription: new Subscription($endsAt),
    );
}

it('возвращает прорату при активной подписке за оставшиеся дни', function (): void {
    $owner = ownerWithSubscriptionEndingAt(new DateTimeImmutable('2026-06-15T00:00:00Z'));
    $owners = fakeOwnerRepository([$owner]);

    $charge = (new CalculatePlaceChargeHandler(
        owners: $owners,
        tariffs: tariffRepoWith(tariffWithExtraPrice(30_000)),
        clock: frozenClockAt('2026-06-01T00:00:00Z'),
        config: new ConfigRepository(['guardreviews.subscription.duration_days' => 30]),
    ))->handle(new CalculatePlaceChargeQuery(ownerId: $owner->id->value));

    expect($charge->daysLeft)->toBe(14)
        ->and($charge->prorataAmount)->toBe(14_000)
        ->and($charge->monthlyDelta)->toBe(30_000)
        ->and($charge->requiresPayment)->toBeTrue();
});

it('не требует оплату при отсутствии активной подписки', function (): void {
    $owner = ownerWithSubscriptionEndingAt(null);
    $owners = fakeOwnerRepository([$owner]);

    $charge = (new CalculatePlaceChargeHandler(
        owners: $owners,
        tariffs: tariffRepoWith(tariffWithExtraPrice(30_000)),
        clock: frozenClockAt('2026-06-01T00:00:00Z'),
        config: new ConfigRepository(['guardreviews.subscription.duration_days' => 30]),
    ))->handle(new CalculatePlaceChargeQuery(ownerId: $owner->id->value));

    expect($charge->daysLeft)->toBe(0)
        ->and($charge->prorataAmount)->toBe(0)
        ->and($charge->monthlyDelta)->toBe(30_000)
        ->and($charge->requiresPayment)->toBeFalse();
});

it('не требует оплату при истекшей подписке', function (): void {
    $owner = ownerWithSubscriptionEndingAt(new DateTimeImmutable('2026-05-01T00:00:00Z'));
    $owners = fakeOwnerRepository([$owner]);

    $charge = (new CalculatePlaceChargeHandler(
        owners: $owners,
        tariffs: tariffRepoWith(tariffWithExtraPrice(30_000)),
        clock: frozenClockAt('2026-06-01T00:00:00Z'),
        config: new ConfigRepository(['guardreviews.subscription.duration_days' => 30]),
    ))->handle(new CalculatePlaceChargeQuery(ownerId: $owner->id->value));

    expect($charge->requiresPayment)->toBeFalse()
        ->and($charge->prorataAmount)->toBe(0);
});

it('возвращает 0 прораты при extra_place_price=0', function (): void {
    $owner = ownerWithSubscriptionEndingAt(new DateTimeImmutable('2026-06-15T00:00:00Z'));
    $owners = fakeOwnerRepository([$owner]);

    $charge = (new CalculatePlaceChargeHandler(
        owners: $owners,
        tariffs: tariffRepoWith(tariffWithExtraPrice(0)),
        clock: frozenClockAt('2026-06-01T00:00:00Z'),
        config: new ConfigRepository(['guardreviews.subscription.duration_days' => 30]),
    ))->handle(new CalculatePlaceChargeQuery(ownerId: $owner->id->value));

    expect($charge->prorataAmount)->toBe(0)
        ->and($charge->requiresPayment)->toBeFalse();
});

it('бросает TenantNotFound для неизвестного владельца', function (): void {
    (new CalculatePlaceChargeHandler(
        owners: fakeOwnerRepository(),
        tariffs: tariffRepoWith(tariffWithExtraPrice(30_000)),
        clock: frozenClockAt('2026-06-01T00:00:00Z'),
        config: new ConfigRepository,
    ))->handle(new CalculatePlaceChargeQuery(ownerId: '00000000-0000-0000-0000-000000000000'));
})->throws(TenantNotFound::class);
