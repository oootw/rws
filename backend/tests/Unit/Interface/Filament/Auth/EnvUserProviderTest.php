<?php

declare(strict_types=1);

use App\Interface\Filament\Auth\AdminUser;
use App\Interface\Filament\Auth\EnvUserProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Hashing\BcryptHasher;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Расширенные тесты EnvUserProvider — фокус на edge cases и
 * семантике методов, которые не покрыты AdminLoginTest.
 */
function makeProvider(?string $email = 'dev@test.local', ?string $hash = null, string $name = 'Dev'): EnvUserProvider
{
    $hasher = new BcryptHasher(['rounds' => 4]);
    $effectiveHash = $hash ?? $hasher->make('test-password-strong-12');

    $config = new ConfigRepository([
        'guardreviews' => [
            'admin' => [
                'email' => $email,
                'password_hash' => $effectiveHash,
                'name' => $name,
            ],
        ],
    ]);

    return new EnvUserProvider($config, $hasher);
}

it('retrieveById возвращает AdminUser для канонического id "admin"', function (): void {
    $user = makeProvider()->retrieveById('admin');

    expect($user)->toBeInstanceOf(AdminUser::class)
        ->and($user->getEmail())->toBe('dev@test.local');
});

it('retrieveById возвращает null для любого другого id', function (): void {
    $provider = makeProvider();

    expect($provider->retrieveById('other'))->toBeNull()
        ->and($provider->retrieveById(0))->toBeNull()
        ->and($provider->retrieveById(''))->toBeNull();
});

it('retrieveById возвращает null если admin не настроен', function (): void {
    expect(makeProvider(email: null)->retrieveById('admin'))->toBeNull()
        ->and(makeProvider(hash: '')->retrieveById('admin'))->toBeNull();
});

it('retrieveByCredentials работает case-insensitive по email', function (): void {
    $provider = makeProvider(email: 'Dev@TEST.local');

    expect($provider->retrieveByCredentials(['email' => 'dev@test.local']))->toBeInstanceOf(AdminUser::class)
        ->and($provider->retrieveByCredentials(['email' => 'DEV@TEST.LOCAL']))->toBeInstanceOf(AdminUser::class);
});

it('retrieveByCredentials возвращает null для чужого email', function (): void {
    expect(makeProvider()->retrieveByCredentials(['email' => 'attacker@evil.io']))->toBeNull();
});

it('retrieveByCredentials возвращает null если email не строка', function (): void {
    $provider = makeProvider();

    expect($provider->retrieveByCredentials([]))->toBeNull()
        ->and($provider->retrieveByCredentials(['email' => null]))->toBeNull()
        ->and($provider->retrieveByCredentials(['email' => 123]))->toBeNull();
});

it('validateCredentials отклоняет не-AdminUser', function (): void {
    $provider = makeProvider();
    $generic = new GenericUser(['id' => 'x', 'password' => 'whatever']);

    expect($provider->validateCredentials($generic, ['password' => 'whatever']))->toBeFalse();
});

it('validateCredentials отклоняет пустой и не-строковый password', function (): void {
    $provider = makeProvider();
    $user = $provider->retrieveById('admin');

    expect($provider->validateCredentials($user, []))->toBeFalse()
        ->and($provider->validateCredentials($user, ['password' => '']))->toBeFalse()
        ->and($provider->validateCredentials($user, ['password' => null]))->toBeFalse()
        ->and($provider->validateCredentials($user, ['password' => 12345]))->toBeFalse();
});

it('validateCredentials возвращает false если у пользователя пустой hash', function (): void {
    $provider = makeProvider(hash: '');
    // retrieveById вернёт null, но смоделируем — собрав AdminUser руками с пустым паролем:
    $user = new AdminUser([
        'id' => 'admin',
        'email' => 'dev@test.local',
        'name' => 'Dev',
        'password' => '',
    ]);

    expect($provider->validateCredentials($user, ['password' => 'whatever']))->toBeFalse();
});

it('retrieveByToken всегда возвращает null (remember-me отключён)', function (): void {
    expect(makeProvider()->retrieveByToken('admin', 'token'))->toBeNull();
});

it('updateRememberToken и rehashPasswordIfRequired — no-op', function (): void {
    $provider = makeProvider();
    $user = $provider->retrieveById('admin');

    // Просто проверяем, что вызовы не падают и ничего не возвращают.
    $provider->updateRememberToken($user, 'any-token');
    $provider->rehashPasswordIfRequired($user, ['password' => 'any']);
    $provider->rehashPasswordIfRequired($user, ['password' => 'any'], force: true);

    expect(true)->toBeTrue();
});
