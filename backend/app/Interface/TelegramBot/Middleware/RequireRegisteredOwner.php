<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Middleware;

use App\Interface\TelegramBot\Support\TelegramOwnerResolver;
use SergiX44\Nutgram\Nutgram;

final class RequireRegisteredOwner
{
    public function __construct(
        private readonly TelegramOwnerResolver $ownerResolver,
    ) {}

    public function __invoke(Nutgram $bot, $next): void
    {
        if ($this->ownerResolver->resolve($bot) === null) {
            $bot->sendMessage('Сначала пройдите регистрацию: /start');

            return;
        }

        $next($bot);
    }
}
