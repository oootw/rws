<?php

declare(strict_types=1);

namespace App\Interface\Filament\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use SensitiveParameter;

/**
 * UserProvider, отдающий «единственного супер-админа» из конфига.
 *
 * Источник правды — config('guardreviews.admin'):
 *  - email       — логин,
 *  - password_hash — bcrypt-хеш пароля (сгенерировать `php artisan admin:password`),
 *  - name        — отображаемое имя.
 *
 * Если email или хеш пустые — провайдер всегда возвращает null:
 * вход в админку запрещён.
 *
 * Пользователь существует только in-memory: нет ремембер-токена,
 * нет обновления пароля, нет таблицы.
 */
final readonly class EnvUserProvider implements UserProvider
{
    /** Стабильный «id» для сессии — провайдер у нас один-единственный пользователь. */
    private const ADMIN_ID = AdminUser::ID;

    public function __construct(
        private Config $config,
        private Hasher $hasher,
    ) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        if ((string) $identifier !== self::ADMIN_ID) {
            return null;
        }

        return $this->buildAdmin();
    }

    public function retrieveByToken($identifier, #[SensitiveParameter] $token): ?Authenticatable
    {
        // Remember-me не поддерживаем намеренно: одноразовая сессия безопаснее.
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[SensitiveParameter] $token): void
    {
        // No-op: токены не сохраняем.
    }

    public function retrieveByCredentials(#[SensitiveParameter] array $credentials): ?Authenticatable
    {
        $email = $credentials['email'] ?? null;

        if (! is_string($email) || $email === '') {
            return null;
        }

        $admin = $this->buildAdmin();

        if ($admin === null) {
            return null;
        }

        return strcasecmp($admin->getEmail(), $email) === 0 ? $admin : null;
    }

    public function validateCredentials(Authenticatable $user, #[SensitiveParameter] array $credentials): bool
    {
        if (! $user instanceof AdminUser) {
            return false;
        }

        $password = $credentials['password'] ?? null;

        if (! is_string($password) || $password === '') {
            return false;
        }

        $hash = $user->getAuthPassword();

        if ($hash === '') {
            return false;
        }

        return $this->hasher->check($password, $hash);
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        #[SensitiveParameter] array $credentials,
        bool $force = false,
    ): void {
        // No-op: пароль живёт в .env, программно ребиндить нельзя.
    }

    private function buildAdmin(): ?AdminUser
    {
        $email = $this->config->get('guardreviews.admin.email');
        $hash = $this->config->get('guardreviews.admin.password_hash');
        $name = $this->config->get('guardreviews.admin.name', 'Developer');

        if (! is_string($email) || $email === '' || ! is_string($hash) || $hash === '') {
            return null;
        }

        return new AdminUser([
            'id' => self::ADMIN_ID,
            'email' => $email,
            'name' => (string) $name,
            'password' => $hash,
        ]);
    }
}
