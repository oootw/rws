<?php

declare(strict_types=1);

use App\Domain\Iam\LoginCodeAlreadyConsumed;
use App\Domain\Iam\LoginCodeExpired;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerLoginRequest;
use App\Domain\Iam\OwnerLoginRequestId;
use App\Domain\Iam\TelegramId;

function issuedLoginRequest(
    string $code = '123456',
    string $issuedAt = '2026-06-01T12:00:00Z',
    int $ttlSeconds = 600,
): OwnerLoginRequest {
    return OwnerLoginRequest::issue(
        id: new OwnerLoginRequestId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        telegramId: new TelegramId('1001'),
        code: $code,
        now: new DateTimeImmutable($issuedAt),
        ttlSeconds: $ttlSeconds,
    );
}

it('бросает ошибку при невалидном формате кода', function (): void {
    OwnerLoginRequest::issue(
        id: new OwnerLoginRequestId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        telegramId: new TelegramId('1001'),
        code: 'ABC123',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 600,
    );
})->throws(InvalidArgumentException::class);

it('бросает ошибку при нулевом TTL', function (): void {
    issuedLoginRequest(ttlSeconds: 0);
})->throws(InvalidArgumentException::class);

it('считается истёкшим после expiresAt', function (): void {
    $request = issuedLoginRequest(ttlSeconds: 600);

    expect($request->isExpiredAt(new DateTimeImmutable('2026-06-01T12:09:59Z')))->toBeFalse()
        ->and($request->isExpiredAt(new DateTimeImmutable('2026-06-01T12:10:00Z')))->toBeTrue()
        ->and($request->isExpiredAt(new DateTimeImmutable('2026-06-01T12:10:01Z')))->toBeTrue();
});

it('помечается consumed после успешного consume', function (): void {
    $request = issuedLoginRequest();

    $request->consume(new DateTimeImmutable('2026-06-01T12:01:00Z'));

    expect($request->isConsumed())->toBeTrue()
        ->and($request->consumedAt()?->format('c'))->toBe('2026-06-01T12:01:00+00:00');
});

it('запрещает повторное использование кода', function (): void {
    $request = issuedLoginRequest();
    $request->consume(new DateTimeImmutable('2026-06-01T12:01:00Z'));

    $request->consume(new DateTimeImmutable('2026-06-01T12:02:00Z'));
})->throws(LoginCodeAlreadyConsumed::class);

it('запрещает использование истёкшего кода', function (): void {
    $request = issuedLoginRequest(ttlSeconds: 60);

    $request->consume(new DateTimeImmutable('2026-06-01T12:02:00Z'));
})->throws(LoginCodeExpired::class);
