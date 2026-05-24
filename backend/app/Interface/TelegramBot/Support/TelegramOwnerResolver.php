<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Support;

use App\Application\Iam\GetOwnerByTelegram\GetOwnerByTelegramHandler;
use App\Application\Iam\GetOwnerByTelegram\GetOwnerByTelegramQuery;
use App\Domain\Iam\Owner;
use SergiX44\Nutgram\Nutgram;

/**
 * Тонкая обёртка над use case GetOwnerByTelegram: знает, как достать
 * Telegram-ID из контекста бота, остальное делает Application-слой.
 */
final readonly class TelegramOwnerResolver
{
    public function __construct(
        private GetOwnerByTelegramHandler $getOwnerByTelegram,
    ) {}

    public function resolve(Nutgram $bot): ?Owner
    {
        $telegramId = $bot->userId();

        if ($telegramId === null) {
            return null;
        }

        return $this->getOwnerByTelegram->handle(
            new GetOwnerByTelegramQuery(telegramId: (string) $telegramId),
        );
    }
}
