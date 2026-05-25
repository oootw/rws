<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerId;
use App\Infrastructure\Iam\SanctumOwnerImpersonationTokenIssuer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

/**
 * Integration-тесты Sanctum-адаптера: проверяем что токен действительно
 * создаётся в БД с правильными atributes, и что он валиден через Sanctum::auth.
 */
it('создаёт запись в personal_access_tokens с правильной ability', function (): void {
    $user = User::factory()->create();
    $expiresAt = new DateTimeImmutable('+15 minutes');

    $issuer = new SanctumOwnerImpersonationTokenIssuer;
    $result = $issuer->issue(new OwnerId((string) $user->id), $expiresAt);

    $token = PersonalAccessToken::query()->first();

    expect($token)->not->toBeNull()
        ->and($token->name)->toBe("impersonation:{$user->id}")
        ->and($token->abilities)->toBe(['impersonated'])
        ->and($token->tokenable_id)->toBe((string) $user->id)
        ->and($token->tokenable_type)->toBe(User::class)
        ->and($token->expires_at?->format('Y-m-d H:i'))
        ->toBe($expiresAt->format('Y-m-d H:i'));

    expect($result['plain_text'])->toBeString()->not->toBeEmpty()
        ->and($result['expires_at'])->toEqual($expiresAt);
});

it('plain-text токен можно использовать как валидный Bearer', function (): void {
    $user = User::factory()->create();
    $expiresAt = new DateTimeImmutable('+15 minutes');

    $result = (new SanctumOwnerImpersonationTokenIssuer)->issue(
        new OwnerId((string) $user->id),
        $expiresAt,
    );

    // Sanctum хранит формат "id|plain". Используем findToken чтобы убедиться,
    // что строка действительно резолвится в нашу запись.
    $found = PersonalAccessToken::findToken($result['plain_text']);

    expect($found)->not->toBeNull()
        ->and($found->can('impersonated'))->toBeTrue()
        ->and($found->can('owner.full-access'))->toBeFalse();
});

it('бросает RuntimeException если владельца удалили между проверкой и выпуском', function (): void {
    $issuer = new SanctumOwnerImpersonationTokenIssuer;

    $issuer->issue(
        new OwnerId('00000000-0000-0000-0000-000000000000'),
        new DateTimeImmutable('+15 minutes'),
    );
})->throws(RuntimeException::class, 'disappeared');

it('каждый вызов создаёт отдельный токен (можно выпустить несколько)', function (): void {
    $user = User::factory()->create();
    $issuer = new SanctumOwnerImpersonationTokenIssuer;

    $issuer->issue(new OwnerId((string) $user->id), new DateTimeImmutable('+15 minutes'));
    $issuer->issue(new OwnerId((string) $user->id), new DateTimeImmutable('+30 minutes'));

    expect(PersonalAccessToken::query()->count())->toBe(2);
});
