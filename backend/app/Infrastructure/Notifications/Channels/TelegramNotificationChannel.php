<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Channels;

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\NotificationAction;
use App\Application\Notifications\OwnerNotification;
use Illuminate\Contracts\Config\Repository;
use Psr\Log\LoggerInterface;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Throwable;

/**
 * Telegram-канал доставки.
 *
 * Таргетов может быть несколько: DM-чат владельца (`telegramId`) и/или
 * групповые чаты (`telegramChatIds`) — все в одном цикле. «Доставлено»
 * считается, если хотя бы один таргет успешно принял сообщение.
 * Если все упали — бросаем {@see TelegramDeliveryFailed}, чтобы
 * MultiChannelOwnerNotifier переключился на fallback (email).
 *
 * Логирование падений каждого таргета — на этом уровне (Multi-канал видит
 * только агрегированное исключение, без потери контекста по чатам).
 */
final readonly class TelegramNotificationChannel implements NotificationChannel
{
    public function __construct(
        private Nutgram $bot,
        private Repository $config,
        private LoggerInterface $logger,
    ) {}

    public function supports(OwnerNotification $notification): bool
    {
        return $this->isBotConfigured()
            && $notification->contact->hasAnyTelegramTarget();
    }

    public function deliver(OwnerNotification $notification): void
    {
        $keyboard = $this->buildKeyboard($notification->actions);
        $delivered = false;

        foreach ($this->collectTargets($notification) as $chatId) {
            try {
                $this->bot->sendMessage(
                    text: $notification->text,
                    chat_id: $chatId,
                    parse_mode: ParseMode::HTML,
                    reply_markup: $keyboard,
                );
                $delivered = true;
            } catch (Throwable $e) {
                $this->logger->warning('Telegram target delivery failed', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $delivered) {
            throw new TelegramDeliveryFailed;
        }
    }

    /**
     * @return list<string>
     */
    private function collectTargets(OwnerNotification $notification): array
    {
        $targets = [
            $notification->contact->telegramId,
            ...$notification->contact->telegramChatIds,
        ];

        return array_values(array_filter(
            $targets,
            static fn (?string $target): bool => $target !== null && $target !== '',
        ));
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
