<?php

declare(strict_types=1);

namespace App\Interface\Filament\Auth;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует UserProvider-драйвер `env-admin` для гарда `admin`.
 * Без этого config/auth.php не сможет инстанциировать провайдера.
 */
final class AdminAuthServiceProvider extends ServiceProvider
{
    public function boot(AuthFactory $auth): void
    {
        $auth->provider('env-admin', function ($app): EnvUserProvider {
            return new EnvUserProvider(
                config: $app->make(Config::class),
                hasher: $app->make(Hasher::class),
            );
        });
    }
}
