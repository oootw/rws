<?php

declare(strict_types=1);

use App\Interface\Filament\Auth\AdminUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

/**
 * Pure-unit на AdminUser — без Laravel, контейнера и БД.
 * Цель: зафиксировать, что in-memory user отдаёт корректные значения
 * для всех Filament-контрактов и Eloquent-совместимых заглушек.
 */
function makeAdminUser(array $overrides = []): AdminUser
{
    return new AdminUser(array_merge([
        'id' => AdminUser::ID,
        'email' => 'dev@test.local',
        'name' => 'Dev',
        'password' => '$2y$04$abcdefghijklmnopqrstuvwxyz0123456789ABCDEF',
    ], $overrides));
}

it('реализует все необходимые Filament-контракты', function (): void {
    $user = makeAdminUser();

    expect($user)->toBeInstanceOf(FilamentUser::class)
        ->and($user)->toBeInstanceOf(HasName::class)
        ->and($user)->toBeInstanceOf(HasAvatar::class);
});

it('canAccessPanel пропускает только admin-панель', function (): void {
    $user = makeAdminUser();

    $admin = Mockery::mock(Panel::class);
    $admin->shouldReceive('getId')->andReturn('admin');

    $other = Mockery::mock(Panel::class);
    $other->shouldReceive('getId')->andReturn('owner');

    expect($user->canAccessPanel($admin))->toBeTrue()
        ->and($user->canAccessPanel($other))->toBeFalse();
});

it('getFilamentName возвращает имя из атрибутов', function (): void {
    expect(makeAdminUser(['name' => 'Hello'])->getFilamentName())->toBe('Hello');
});

it('getFilamentName fallback на "Admin" при отсутствии name', function (): void {
    $user = new AdminUser(['id' => AdminUser::ID, 'email' => 'x@x.io', 'password' => 'h']);

    expect($user->getFilamentName())->toBe('Admin');
});

it('getEmail возвращает email из атрибутов', function (): void {
    expect(makeAdminUser(['email' => 'a@b.c'])->getEmail())->toBe('a@b.c');
});

it('getFilamentAvatarUrl возвращает null (используется дефолтный provider)', function (): void {
    expect(makeAdminUser()->getFilamentAvatarUrl())->toBeNull();
});

it('getKey возвращает стабильный идентификатор', function (): void {
    expect(makeAdminUser()->getKey())->toBe(AdminUser::ID);
});

it('getKey fallback на канонический UUID если id отсутствует', function (): void {
    $user = new AdminUser(['email' => 'x@x.io', 'password' => 'h', 'name' => 'A']);

    expect($user->getKey())->toBe(AdminUser::ID);
});

it('getAttributeValue читает атрибуты из массива', function (): void {
    $user = makeAdminUser(['name' => 'X', 'email' => 'e@e.e']);

    expect($user->getAttributeValue('name'))->toBe('X')
        ->and($user->getAttributeValue('email'))->toBe('e@e.e')
        ->and($user->getAttributeValue('nonexistent'))->toBeNull();
});

it('getAuthPassword возвращает hashed пароль', function (): void {
    $user = makeAdminUser(['password' => '$2y$04$test-hash']);

    expect($user->getAuthPassword())->toBe('$2y$04$test-hash');
});

it('getAuthIdentifier возвращает id', function (): void {
    expect(makeAdminUser()->getAuthIdentifier())->toBe(AdminUser::ID);
});

it('remember token отключён и setRememberToken — no-op', function (): void {
    $user = makeAdminUser();

    expect($user->getRememberToken())->toBeNull();

    $user->setRememberToken('should-be-ignored');

    expect($user->getRememberToken())->toBeNull();
});
