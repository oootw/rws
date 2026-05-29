<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Commands;

use App\Application\Iam\BindTelegramChat\BindTelegramChatCommand;
use App\Application\Iam\BindTelegramChat\BindTelegramChatHandler;
use App\Application\Iam\Exceptions\ChatLinkTokenNotFound;
use App\Domain\Iam\ChatLinkTokenAlreadyConsumed;
use App\Domain\Iam\ChatLinkTokenExpired;
use App\Interface\TelegramBot\Conversations\OnboardingConversation;
use App\Interface\TelegramBot\Support\TelegramMessages;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

/**
 * Обрабатывает `/start <token>` — deep-link `?startgroup=<token>` приводит к
 * тому, что бот, попав в группу, получает эту команду с токеном привязки.
 *
 * Токен — это и есть авторизация: он уже привязан к конкретному владельцу,
 * поэтому проверять отправителя через RequireRegisteredOwner не нужно.
 * Доменные/прикладные ошибки токена превращаем в человекочитаемое сообщение.
 *
 * В личке `/start <token>` смысла не имеет (привязываем только групповые
 * чаты), поэтому деградируем к обычному онбордингу.
 */
final readonly class ChatLinkCommandHandler
{
    public function __construct(
        private BindTelegramChatHandler $bindChat,
    ) {}

    public function __invoke(Nutgram $bot, string $token): void
    {
        $chat = $bot->chat();

        if ($chat === null || ! $this->isGroupChat($chat->type)) {
            OnboardingConversation::begin($bot);

            return;
        }

        try {
            $this->bindChat->handle(new BindTelegramChatCommand(
                token: $token,
                chatId: (string) $chat->id,
                title: $chat->title,
            ));
        } catch (ChatLinkTokenNotFound|ChatLinkTokenExpired|ChatLinkTokenAlreadyConsumed) {
            $bot->sendMessage(TelegramMessages::chatLinkInvalid());

            return;
        }

        $bot->sendMessage(TelegramMessages::chatLinked());
    }

    private function isGroupChat(ChatType|string $type): bool
    {
        $value = $type instanceof ChatType ? $type->value : $type;

        return $value === ChatType::GROUP->value
            || $value === ChatType::SUPERGROUP->value;
    }
}
