<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\TariffNotFound;
use App\Application\Iam\SetDefaultTariff\SetDefaultTariffCommand;
use App\Application\Iam\SetDefaultTariff\SetDefaultTariffHandler;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;

it('делает указанный тариф default и сбрасывает остальных', function (): void {
    $mvp = new Tariff(new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'), 'MVP', isDefault: true);
    $plus = new Tariff(new TariffId('dddddddd-dddd-dddd-dddd-dddddddddddd'), 'Plus', isDefault: false);
    $tariffs = fakeTariffRepository($mvp, [$mvp, $plus]);

    (new SetDefaultTariffHandler($tariffs, immediateTransactionRunner()))->handle(
        new SetDefaultTariffCommand(tariffId: $plus->id->value),
    );

    expect($tariffs->default?->id->value)->toBe($plus->id->value)
        ->and($tariffs->tariffs[0]->isDefault)->toBeFalse()
        ->and($tariffs->tariffs[1]->isDefault)->toBeTrue();
});

it('бросает TariffNotFound для неизвестного тарифа', function (): void {
    (new SetDefaultTariffHandler(fakeTariffRepository(), immediateTransactionRunner()))->handle(
        new SetDefaultTariffCommand(tariffId: '00000000-0000-0000-0000-000000000000'),
    );
})->throws(TariffNotFound::class);
