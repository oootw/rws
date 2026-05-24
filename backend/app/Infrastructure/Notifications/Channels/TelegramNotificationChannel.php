<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Channels;

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\NotificationAction;
use App\Application\Notifications\OwnerNotification;
use Illuminate\Contracts\Config\Repository;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final readonly class TelegramNotificationChannel implements NotificationChannel
{
    public function __construct(
        private Nutgram $bot,
        private Repository $config,
    ) {}

    public function supports(OwnerNotification $notification): bool
    {
        return $this->isBotConfigured()
            && $notification->contact->telegramId !== null;
    }

    public function deliver(OwnerNotification $notification): void
    {
        $this->bot->sendMessage(
            text: $notification->text,
            chat_id: $notification->contact->telegramId,
            parse_mode: ParseMode::HTML,
            reply_markup: $this->buildKeyboard($notification->actions),
        );
    }

    private function isBotConfigured(): bool
    {
        $token = $this->config->get('nutgram.token');

        return is_string($token) && $token !== '';
    }

    /**
     * @param  list<NotificationAction>  $actions
     */
    private function buildKeyboard(array $actions): ?InlineKeyboardMarkup
    {
        if ($actions === []) {
            return null;
        }

        $keyboard = InlineKeyboardMarkup::make();

        foreach ($actions as $action) {
            $keyboard->addRow(
                InlineKeyboardButton::make($action->label, callback_data: $action->payload),
            );
        }

        return $keyboard;
    }
}
