<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Commands;

use App\Application\Iam\Exceptions\OwnerNotLinkedToTelegram;
use App\Application\Iam\RequestOwnerLogin\RequestOwnerLoginCommand;
use App\Application\Iam\RequestOwnerLogin\RequestOwnerLoginHandler;
use Illuminate\Contracts\Config\Repository as Config;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Команда /login — выдаёт одноразовый код для входа в SPA-кабинет.
 * Деплинк включает code, чтобы кнопка автоматически логинила без копипасты.
 */
final readonly class LoginCommandHandler
{
    public function __construct(
        private RequestOwnerLoginHandler $requestLogin,
        private Config $config,
    ) {}

    public function __invoke(Nutgram $bot): void
    {
        $telegramId = $bot->userId();

        if ($telegramId === null) {
            return;
        }

        try {
            $issued = $this->requestLogin->handle(
                new RequestOwnerLoginCommand(telegramId: (string) $telegramId),
            );
        } catch (OwnerNotLinkedToTelegram) {
            $bot->sendMessage('Этот Telegram не привязан к кабинету. Используйте /start для регистрации.');

            return;
        }

        $domain = (string) $this->config->get('guardreviews.domain');
        $url = "https://{$issued->subdomain->value}.{$domain}/owner/login?code={$issued->code}";
        $ttlMinutes = max(1, (int) round(
            ($issued->expiresAt->getTimestamp() - time()) / 60,
        ));

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make('Открыть кабинет', url: $url),
        );

        $bot->sendMessage(
            "Код для входа: <b>{$issued->code}</b>\n".
            "Действует {$ttlMinutes} мин.\n\n".
            'Откройте кабинет кнопкой ниже или введите код вручную.',
            parse_mode: ParseMode::HTML,
            reply_markup: $keyboard,
        );
    }
}
