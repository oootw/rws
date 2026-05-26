<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerLoginRequestId;
use App\Infrastructure\Persistence\Eloquent\Iam\UuidOwnerLoginRequestIdGenerator;

it('генерирует валидный UUID как OwnerLoginRequestId', function (): void {
    $generator = new UuidOwnerLoginRequestIdGenerator;

    $id = $generator->next();

    expect($id)->toBeInstanceOf(OwnerLoginRequestId::class)
        ->and($id->value)->toMatch(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        );
});

it('генерирует разные id при последовательных вызовах', function (): void {
    $generator = new UuidOwnerLoginRequestIdGenerator;

    $first = $generator->next();
    $second = $generator->next();

    expect($first->equals($second))->toBeFalse();
});
