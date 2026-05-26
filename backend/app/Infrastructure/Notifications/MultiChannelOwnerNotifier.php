<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\Logging\NotificationDeliveryLogger;
use App\Application\Notifications\Logging\NotificationDeliveryStatus;
use App\Application\Notifications\OwnerNotification;
use App\Application\Notifications\OwnerNotifier;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

/**
 * Многоканальная доставка уведомлений.
 *
 * Алгоритм:
 *  1) Перебираем instant-каналы (Telegram, MAX). Если хоть один отдал
 *     уведомление БЕЗ исключения — fallback (email) не дёргаем.
 *  2) Если ни один instant не сработал — пробуем все fallback-каналы.
 *  3) Если и они не сработали — пробрасываем NotificationDeliveryFailed,
 *     чтобы job отправился на retry / в failed_jobs (ничего не теряем).
 *
 * Исключения отдельных каналов логируются, но не прерывают цепочку:
 * упавший Telegram не мешает попробовать MAX и затем email.
 *
 * Каждая попытка пишется в notification_deliveries через
 * NotificationDeliveryLogger — это «тихая» запись для админ-просмотра;
 * её падение по контракту не должно затрагивать основной flow.
 *
 * @phpstan-type ChannelList list<NotificationChannel>
 */
final readonly class MultiChannelOwnerNotifier implements OwnerNotifier
{
    /**
     * @param  ChannelList  $instantChannels  каналы реального времени (Telegram, MAX)
     * @param  ChannelList  $fallbackChannels  каналы-резерв (Email) — вызываются, только если ни один instant не сработал
     */
    public function __construct(
        private array $instantChannels,
        private array $fallbackChannels,
        private LoggerInterface $logger,
        private NotificationDeliveryLogger $deliveryLogger,
    ) {}

    public function notify(OwnerNotification $notification): void
    {
        $errors = [];

        if ($this->deliverThrough($this->instantChannels, $notification, $errors)) {
            return;
        }

        if ($this->deliverThrough($this->fallbackChannels, $notification, $errors)) {
            return;
        }

        throw new NotificationDeliveryFailed(
            'Не удалось доставить уведомление ни одним из каналов.',
            $errors,
        );
    }

    /**
     * @param  ChannelList  $channels
     * @param  list<array{channel: class-string, error: string}>  $errors
     */
    private function deliverThrough(array $channels, OwnerNotification $notification, array &$errors): bool
    {
        $delivered = false;

        foreach ($channels as $channel) {
            $channelName = $this->channelName($channel);

            if (! $channel->supports($notification)) {
                $this->deliveryLogger->log(
                    ownerId: $notification->contact->ownerId,
                    channel: $channelName,
                    kind: $notification->kind,
                    status: NotificationDeliveryStatus::Skipped,
                );

                continue;
            }

            try {
                $channel->deliver($notification);
                $delivered = true;

                $this->deliveryLogger->log(
                    ownerId: $notification->contact->ownerId,
                    channel: $channelName,
                    kind: $notification->kind,
                    status: NotificationDeliveryStatus::Delivered,
                );
            } catch (Throwable $e) {
                $errors[] = [
                    'channel' => $channel::class,
                    'error' => $e->getMessage(),
                ];

                $this->logger->warning('Notification channel failed', [
                    'channel' => $channel::class,
                    'subject' => $notification->emailSubject,
                    'error' => $e->getMessage(),
                ]);

                $this->deliveryLogger->log(
                    ownerId: $notification->contact->ownerId,
                    channel: $channelName,
                    kind: $notification->kind,
                    status: NotificationDeliveryStatus::Failed,
                    error: $e->getMessage(),
                );
            }
        }

        return $delivered;
    }

    /**
     * Короткое имя адаптера для notification_deliveries («Telegram», «Max», «Email»),
     * чтобы админ видел осмысленный канал, а не FQCN.
     */
    private function channelName(NotificationChannel $channel): string
    {
        $short = (new ReflectionClass($channel))->getShortName();

        return str_replace('NotificationChannel', '', $short) ?: $short;
    }
}
