<?php

declare(strict_types=1);

use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffCommand;
use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffHandler;
use App\Application\Iam\Exceptions\TariffNotFound;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;

it('меняет тариф владельца', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);
    $newTariff = new Tariff(new TariffId('99999999-9999-9999-9999-999999999999'), 'Plus');
    $tariffs = fakeTariffRepository(tariffs: [$newTariff]);

    (new ChangeOwnerTariffHandler($owners, $tariffs))->handle(
        new ChangeOwnerTariffCommand(ownerId: $owner->id->value, tariffId: $newTariff->id->value),
    );

    expect($owners->owners[0]->tariffId()?->value)->toBe($newTariff->id->value);
});

it('сбрасывает тариф при tariffId = null', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);

    (new ChangeOwnerTariffHandler($owners, fakeTariffRepository()))->handle(
        new ChangeOwnerTariffCommand(ownerId: $owner->id->value, tariffId: null),
    );

    expect($owners->owners[0]->tariffId())->toBeNull();
});

it('бросает TariffNotFound для неизвестного тарифа', function (): void {
    $owner = restoredOwner();
    (new ChangeOwnerTariffHandler(
        fakeOwnerRepository([$owner]),
        fakeTariffRepository(),
    ))->handle(new ChangeOwnerTariffCommand(
        ownerId: $owner->id->value,
        tariffId: '88888888-8888-8888-8888-888888888888',
    ));
})->throws(TariffNotFound::class);

it('бросает TenantNotFound для неизвестного владельца', function (): void {
    (new ChangeOwnerTariffHandler(fakeOwnerRepository(), fakeTariffRepository()))->handle(
        new ChangeOwnerTariffCommand(
            ownerId: '00000000-0000-0000-0000-000000000000',
            tariffId: null,
        ),
    );
})->throws(TenantNotFound::class);
