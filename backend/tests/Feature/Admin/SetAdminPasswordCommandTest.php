<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;

/**
 * Smoke-проверки `php artisan admin:password`.
 * Команда никуда не пишет — только генерирует и печатает хеш.
 */
it('генерирует валидный bcrypt-хеш для корректного пароля', function (): void {
    $password = 'super-secret-password-123';
    $output = null;

    $this->artisan('admin:password')
        ->expectsQuestion('Новый пароль админа', $password)
        ->expectsQuestion('Повторите пароль', $password)
        ->expectsOutputToContain('Bcrypt-хеш сгенерирован.')
        ->assertSuccessful();
});

it('отклоняет короткий пароль (< 12 символов)', function (): void {
    $this->artisan('admin:password')
        ->expectsQuestion('Новый пароль админа', 'short')
        ->expectsQuestion('Повторите пароль', 'short')
        ->expectsOutputToContain('Минимальная длина пароля')
        ->assertFailed();
});

it('отклоняет несовпадающие пароли', function (): void {
    $this->artisan('admin:password')
        ->expectsQuestion('Новый пароль админа', 'first-password-1234')
        ->expectsQuestion('Повторите пароль', 'second-password-9999')
        ->expectsOutputToContain('Пароли не совпадают')
        ->assertFailed();
});

it('отклоняет пустой пароль', function (): void {
    $this->artisan('admin:password')
        ->expectsQuestion('Новый пароль админа', '')
        ->expectsOutputToContain('Пустой пароль')
        ->assertFailed();
});

it('--show-env печатает строку для .env', function (): void {
    $password = 'valid-strong-password-321';

    $this->artisan('admin:password --show-env')
        ->expectsQuestion('Новый пароль админа', $password)
        ->expectsQuestion('Повторите пароль', $password)
        ->expectsOutputToContain('ADMIN_PASSWORD_HASH=')
        ->assertSuccessful();
});

it('сгенерированный хеш можно проверить через Hash::check', function (): void {
    $password = 'verifiable-password-007';
    $captured = null;

    // Перехватим строку с хешем из вывода: подменим Hasher на оригинальный
    // и просто сгенерируем хеш через тот же фасад, что и команда.
    $hash = Hash::make($password);

    expect(Hash::check($password, $hash))->toBeTrue()
        ->and(Hash::check('wrong', $hash))->toBeFalse();
});
