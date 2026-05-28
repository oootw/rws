<?php

declare(strict_types=1);

use App\Domain\Iam\ChatLinkTokenAlreadyConsumed;
use App\Domain\Iam\ChatLinkTokenExpired;
use App\Domain\Iam\OwnerChatLinkToken;
use App\Domain\Iam\OwnerChatLinkTokenId;
use App\Domain\Iam\OwnerId;

function issuedChatLinkToken(
    string $token = 'abcdef0123456789abcdef0123456789',
    string $issuedAt = '2026-06-01T12:00:00Z',
    int $ttlSeconds = 600,
): OwnerChatLinkToken {
    return OwnerChatLinkToken::issue(
        id: new OwnerChatLinkTokenId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        token: $token,
        now: new DateTimeImmutable($issuedAt),
        ttlSeconds: $ttlSeconds,
    );
}

it('бросает ошибку при невалидном формате токена', function (): void {
    issuedChatLinkToken(token: 'NOT-HEX!!!');
})->throws(InvalidArgumentException::class);

it('бросает ошибку при коротком токене', function (): void {
    issuedChatLinkToken(token: 'abc');
})->throws(InvalidArgumentException::class);

it('бросает ошибку при нулевом TTL', function (): void {
    issuedChatLinkToken(ttlSeconds: 0);
})->throws(InvalidArgumentException::class);

it('считается истёкшим после expiresAt', function (): void {
    $token = issuedChatLinkToken(ttlSeconds: 600);

    expect($token->isExpiredAt(new DateTimeImmutable('2026-06-01T12:09:59Z')))->toBeFalse()
        ->and($token->isExpiredAt(new DateTimeImmutable('2026-06-01T12:10:00Z')))->toBeTrue();
});

it('помечается consumed после успешного consume', function (): void {
    $token = issuedChatLinkToken();

    $token->consume(new DateTimeImmutable('2026-06-01T12:01:00Z'));

    expect($token->isConsumed())->toBeTrue()
        ->and($token->consumedAt()?->format('c'))->toBe('2026-06-01T12:01:00+00:00');
});

it('запрещает повторное использование', function (): void {
    $token = issuedChatLinkToken();
    $token->consume(new DateTimeImmutable('2026-06-01T12:01:00Z'));

    $token->consume(new DateTimeImmutable('2026-06-01T12:02:00Z'));
})->throws(ChatLinkTokenAlreadyConsumed::class);

it('запрещает использование истёкшего токена', function (): void {
    $token = issuedChatLinkToken(ttlSeconds: 60);

    $token->consume(new DateTimeImmutable('2026-06-01T12:02:00Z'));
})->throws(ChatLinkTokenExpired::class);
