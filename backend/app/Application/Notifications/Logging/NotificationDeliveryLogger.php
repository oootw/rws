<?php

declare(strict_types=1);

namespace App\Application\Notifications\Logging;

/**
 * Порт: запись результата попытки доставки уведомления одним каналом.
 *
 * Реализация должна быть «тихой»: упавший запрос в БД не имеет права
 * сорвать основной flow уведомлений (см. контракт NotificationChannel
 * и MultiChannelOwnerNotifier).
 */
interface NotificationDeliveryLogger
{
    public function log(
        ?string $ownerId,
        string $channel,
        string $kind,
        NotificationDeliveryStatus $status,
        ?string $error = null,
    ): void;
}
