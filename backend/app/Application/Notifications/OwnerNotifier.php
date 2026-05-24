<?php

declare(strict_types=1);

namespace App\Application\Notifications;

/**
 * Точка входа в подсистему уведомлений: получить OwnerNotification и
 * доставить его подходящим способом. Стратегия выбора канала и фолбэк —
 * деталь реализации (см. инфраструктурный MultiChannelOwnerNotifier).
 */
interface OwnerNotifier
{
    public function notify(OwnerNotification $notification): void;
}
