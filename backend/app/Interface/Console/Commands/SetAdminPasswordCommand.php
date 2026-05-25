<?php

declare(strict_types=1);

namespace App\Interface\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Генерирует bcrypt-хеш пароля для супер-админа Filament-панели.
 *
 * Хеш кладём в .env как ADMIN_PASSWORD_HASH. Сам пароль никуда не сохраняем —
 * команда только печатает результат, чтобы разработчик скопировал.
 *
 * Использование:
 *   php artisan admin:password
 *   php artisan admin:password --show-env  # покажет готовую строку .env
 */
final class SetAdminPasswordCommand extends Command
{
    protected $signature = 'admin:password
        {--show-env : Вывести готовую строку для .env}';

    protected $description = 'Генерирует bcrypt-хеш пароля админа для ADMIN_PASSWORD_HASH';

    public function handle(Hasher $hasher): int
    {
        $password = $this->securePrompt('Новый пароль админа');

        if ($password === null) {
            return self::FAILURE;
        }

        $confirmation = $this->securePrompt('Повторите пароль');

        if ($confirmation !== $password) {
            $this->error('Пароли не совпадают.');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('Минимальная длина пароля — 12 символов.');

            return self::FAILURE;
        }

        $hash = $hasher->make($password);

        $this->newLine();
        $this->info('Bcrypt-хеш сгенерирован. Положите в .env:');
        $this->newLine();

        if ($this->option('show-env')) {
            $this->line("ADMIN_PASSWORD_HASH='{$hash}'");
        } else {
            $this->line($hash);
        }

        $this->newLine();
        $this->warn('После обновления .env: php artisan config:clear');

        return self::SUCCESS;
    }

    private function securePrompt(string $label): ?string
    {
        $value = $this->secret($label);

        if (! is_string($value) || $value === '') {
            $this->error('Пустой пароль не принимается.');

            return null;
        }

        return $value;
    }
}
