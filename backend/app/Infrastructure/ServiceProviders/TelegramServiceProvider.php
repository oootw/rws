<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Infrastructure\Telegram\TelegramFailoverMiddleware;
use GuzzleHttp\HandlerStack;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Тюнинг Nutgram-клиента под наш сетап:
 *  - если задан TELEGRAM_API_URLS (CSV) — заворачиваем HTTP-клиент в
 *    HandlerStack с failover-middleware (первый URL — primary, остальные —
 *    резерв на случай падения прокси);
 *  - первый URL становится `nutgram.config.api_url` (Nutgram'у нужен один
 *    base, middleware на лету переписывает host'ы при ошибках).
 *
 * Провайдер регистрируется ПОСЛЕ NutgramServiceProvider (auto-discovered),
 * поэтому мутация nutgram.config.client успевает до первого `app(Nutgram::class)`.
 */
final class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app->make(Config::class);

        $urls = $this->parseUrls((string) $config->get('guardreviews.telegram.api_urls', ''));

        if ($urls === []) {
            return;
        }

        $primary = $urls[0];
        $config->set('nutgram.config.api_url', $primary);

        if (count($urls) === 1) {
            return;
        }

        // Подменяем Guzzle handler стэком, в который втыкаем наш middleware.
        $this->app->resolving('config', function (): void {});

        $stackFactory = function (Application $app) use ($urls): HandlerStack {
            $stack = HandlerStack::create();
            $stack->push(new TelegramFailoverMiddleware(
                urls: $urls,
                logger: $app->make(LoggerInterface::class)->channel(
                    (string) config('nutgram.log_channel', 'stack'),
                ),
            ), 'telegram_failover');

            return $stack;
        };

        $client = (array) $config->get('nutgram.config.client', []);
        $client['handler'] = $stackFactory($this->app);
        $config->set('nutgram.config.client', $client);
    }

    /**
     * @return list<string>
     */
    private function parseUrls(string $csv): array
    {
        if ($csv === '') {
            return [];
        }

        $urls = array_values(array_filter(
            array_map('trim', explode(',', $csv)),
            static fn (string $url): bool => $url !== '',
        ));

        return $urls;
    }
}
