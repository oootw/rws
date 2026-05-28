<?php

declare(strict_types=1);

namespace App\Interface\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Генерирует VAPID-пару для Web Push.
 *
 * Команда только печатает значения в stdout — .env она не трогает,
 * чтобы случайно не перезаписать прод-секреты. Скопировать в
 * VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY вручную (или в secrets деплоя).
 */
final class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:generate-vapid {--for-env : Вывести готовые строки для .env}';

    protected $description = 'Печатает новую VAPID-пару для Web Push (в stdout, не пишет в .env)';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        if ($this->option('for-env')) {
            $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
            $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");

            return self::SUCCESS;
        }

        $this->info('VAPID keys generated. Положите в .env и в секреты деплоя:');
        $this->newLine();
        $this->line("Public key:  {$keys['publicKey']}");
        $this->line("Private key: {$keys['privateKey']}");
        $this->newLine();
        $this->warn('Не теряйте private key — без него существующие подписки перестанут работать.');

        return self::SUCCESS;
    }
}
