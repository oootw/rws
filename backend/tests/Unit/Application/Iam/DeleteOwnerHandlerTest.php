<?php

declare(strict_types=1);

use App\Application\Iam\DeleteOwner\DeleteOwnerCommand;
use App\Application\Iam\DeleteOwner\DeleteOwnerHandler;
use App\Application\Iam\Exceptions\TenantNotFound;

it('удаляет существующего владельца', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);

    (new DeleteOwnerHandler($owners))->handle(
        new DeleteOwnerCommand(ownerId: $owner->id->value),
    );

    expect($owners->owners)->toBeEmpty();
});

it('бросает TenantNotFound для неизвестного владельца', function (): void {
    (new DeleteOwnerHandler(fakeOwnerRepository()))->handle(
        new DeleteOwnerCommand(ownerId: '00000000-0000-0000-0000-000000000000'),
    );
})->throws(TenantNotFound::class);
