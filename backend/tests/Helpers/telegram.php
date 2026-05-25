<?php

declare(strict_types=1);

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User as TelegramUser;

function fakeTelegramUser(int $id = 1001, string $firstName = 'Иван'): TelegramUser
{
    return TelegramUser::make(
        id: $id,
        is_bot: false,
        first_name: $firstName,
    );
}

function fakeTelegramChat(int $id = 1001): Chat
{
    return Chat::make(
        id: $id,
        type: 'private',
    );
}

function telegramBot(): Nutgram
{
    return app(Nutgram::class);
}

function resetTelegramBot(): void
{
    app()->forgetInstance(Nutgram::class);
    app()->forgetInstance('nutgram');
    app()->forgetInstance('telegram');
}

function registeredTelegramBot(int $telegramId = 2002): Nutgram
{
    return telegramBot()
        ->setCommonUser(fakeTelegramUser($telegramId))
        ->setCommonChat(fakeTelegramChat($telegramId));
}

function assertTelegramReplyContains(Nutgram $bot, string $needle): void
{
    $history = method_exists($bot, 'getRequestHistory')
        ? $bot->getRequestHistory()
        : [];

    foreach ($history as $reqRes) {
        [$request] = array_values($reqRes);

        if (str_contains((string) $request->getBody(), $needle)) {
            expect(true)->toBeTrue();

            return;
        }
    }

    expect(false)->toBeTrue("Expected telegram reply to contain: {$needle}");
}
