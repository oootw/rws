<?php

declare(strict_types=1);

use App\Interface\Filament\Auth\AdminUser;
use Illuminate\Support\Facades\Hash;

/**
 * Smoke-проверки Filament-админки: маршруты живые,
 * вход и логаут работают только с корректным паролем,
 * без учётки в .env — доступа нет вовсе.
 */
beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
        'guardreviews.admin.name' => 'Test Dev',
    ]);
});

it('редиректит гостя на /admin/login', function (): void {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('отдаёт страницу логина админки', function (): void {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Guard Reviews');
});

it('пускает на дашборд с верными credentials', function (): void {
    $this->actingAs(
        new AdminUser([
            'id' => 'admin',
            'email' => 'dev@test.local',
            'name' => 'Test Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin'
    );

    $this->get('/admin')->assertOk();
});

it('считает пароль валидным через EnvUserProvider', function (): void {
    $provider = auth()->createUserProvider('env-admin');

    expect($provider)->not->toBeNull();

    $user = $provider->retrieveByCredentials(['email' => 'dev@test.local']);

    expect($user)->toBeInstanceOf(AdminUser::class)
        ->and($provider->validateCredentials($user, ['password' => 'test-password-strong-12']))->toBeTrue()
        ->and($provider->validateCredentials($user, ['password' => 'wrong-password']))->toBeFalse();
});

it('запрещает вход, если ADMIN_EMAIL не задан', function (): void {
    config([
        'guardreviews.admin.email' => null,
        'guardreviews.admin.password_hash' => null,
    ]);

    $provider = auth()->createUserProvider('env-admin');

    expect($provider->retrieveByCredentials(['email' => 'dev@test.local']))->toBeNull();
});

it('игнорирует regex-поиск по email при пустом email в credentials', function (): void {
    $provider = auth()->createUserProvider('env-admin');

    expect($provider->retrieveByCredentials([]))->toBeNull()
        ->and($provider->retrieveByCredentials(['email' => '']))->toBeNull();
});
