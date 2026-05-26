<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Logging;

use App\Application\Notifications\Logging\NotificationDeliveryLogger;
use App\Application\Notifications\Logging\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Записывает попытку доставки в notification_deliveries.
 *
 * «Тихая» реализация: упавший insert не имеет права прерывать цепочку
 * каналов в MultiChannelOwnerNotifier — поэтому ловим Throwable и
 * пишем только в системный лог.
 */
final readonly class EloquentNotificationDeliveryLogger implements NotificationDeliveryLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function log(
        ?string $ownerId,
        string $channel,
        string $kind,
        NotificationDeliveryStatus $status,
        ?string $error = null,
    ): void {
        try {
            NotificationDelivery::query()->create([
                'owner_id' => $ownerId,
                'channel' => $channel,
                'kind' => $kind,
                'status' => $status->value,
                'error' => $error !== null ? mb_substr($error, 0, 4000) : null,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('Не удалось записать notification_delivery', [
                'error' => $e->getMessage(),
                'channel' => $channel,
                'kind' => $kind,
            ]);
        }
    }
}
