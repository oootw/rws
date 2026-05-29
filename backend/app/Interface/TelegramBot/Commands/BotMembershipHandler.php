<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Commands;

use App\Interface\TelegramBot\Support\TelegramMessages;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatMemberStatus;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

/**
 * Реагирует на `my_chat_member`: когда бота добавили в групповой чат, шлём
 * подсказку, как привязать чат к аккаунту владельца через панель.
 *
 * Это путь для тех, кто добавил бота в группу вручную (без deep-link
 * `?startgroup=<token>`). При добавлении через deep-link привязка произойдёт
 * автоматически по `/start <token>` — подсказка тут безвредна.
 */
final readonly class BotMembershipHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $update = $bot->chatMember();
        $chat = $bot->chat();

        if ($update === null || $chat === null || ! $this->isGroupChat($chat->type)) {
            return;
        }

        if (! $this->isJoinEvent(
            old: $update->old_chat_member->status,
            new: $update->new_chat_member->status,
        )) {
            return;
        }

        $bot->sendMessage(TelegramMessages::chatLinkHint(), chat_id: $chat->id);
    }

    private function isJoinEvent(ChatMemberStatus|string $old, ChatMemberStatus|string $new): bool
    {
        return $this->wasOutside($old) && $this->isInside($new);
    }

    private function wasOutside(ChatMemberStatus|string $status): bool
    {
        $value = $this->statusValue($status);

        return $value === ChatMemberStatus::LEFT->value
            || $value === ChatMemberStatus::KICKED->value;
    }

    private function isInside(ChatMemberStatus|string $status): bool
    {
        $value = $this->statusValue($status);

        return $value === ChatMemberStatus::MEMBER->value
            || $value === ChatMemberStatus::ADMINISTRATOR->value;
    }

    private function statusValue(ChatMemberStatus|string $status): string
    {
        return $status instanceof ChatMemberStatus ? $status->value : $status;
    }

    private function isGroupChat(ChatType|string $type): bool
    {
        $value = $type instanceof ChatType ? $type->value : $type;

        return $value === ChatType::GROUP->value
            || $value === ChatType::SUPERGROUP->value;
    }
}
