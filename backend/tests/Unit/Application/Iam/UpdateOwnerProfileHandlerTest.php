<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileCommand;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileHandler;

it('обновляет профиль владельца', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);

    (new UpdateOwnerProfileHandler($owners))->handle(new UpdateOwnerProfileCommand(
        ownerId: $owner->id->value,
        name: 'Пётр',
        email: 'new@example.com',
        subdomain: 'newcafe',
        telegramId: '2002',
        tariffId: 'cccccccc-cccc-cccc-cccc-cccccccccccc',
    ));

    $stored = $owners->owners[0];

    expect($stored->name())->toBe('Пётр')
        ->and($stored->email()->value)->toBe('new@example.com')
        ->and($stored->subdomain()->value)->toBe('newcafe')
        ->and($stored->telegramId()?->value)->toBe('2002');
});

it('бросает TenantNotFound для неизвестного владельца', function (): void {
    (new UpdateOwnerProfileHandler(fakeOwnerRepository()))->handle(new UpdateOwnerProfileCommand(
        ownerId: '00000000-0000-0000-0000-000000000000',
        name: 'A',
        email: 'a@a.test',
        subdomain: 'foo',
        telegramId: null,
        tariffId: null,
    ));
})->throws(TenantNotFound::class);

it('не считает текущий поддомен занятым (нет переименования)', function (): void {
    $owner = restoredOwner();
    // Симулируем что slug "cafe" числится "занятым" — это сам владелец.
    $owners = fakeOwnerRepository([$owner], takenSubdomains: ['cafe']);

    (new UpdateOwnerProfileHandler($owners))->handle(new UpdateOwnerProfileCommand(
        ownerId: $owner->id->value,
        name: 'Иван',
        email: 'owner@example.com',
        subdomain: 'cafe', // тот же
        telegramId: '1001',
        tariffId: null,
    ));

    expect($owners->owners[0]->subdomain()->value)->toBe('cafe');
});

it('запрещает переименование на занятый поддомен', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner], takenSubdomains: ['busy']);

    (new UpdateOwnerProfileHandler($owners))->handle(new UpdateOwnerProfileCommand(
        ownerId: $owner->id->value,
        name: 'Иван',
        email: 'owner@example.com',
        subdomain: 'busy',
        telegramId: null,
        tariffId: null,
    ));
})->throws(SubdomainAlreadyTaken::class);

it('допускает пустые telegramId и tariffId как null', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);

    (new UpdateOwnerProfileHandler($owners))->handle(new UpdateOwnerProfileCommand(
        ownerId: $owner->id->value,
        name: 'Иван',
        email: 'owner@example.com',
        subdomain: 'cafe',
        telegramId: '',
        tariffId: '',
    ));

    expect($owners->owners[0]->telegramId())->toBeNull()
        ->and($owners->owners[0]->tariffId())->toBeNull();
});
