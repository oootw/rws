<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Channels;

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\OwnerNotification;
use Illuminate\Contracts\Config\Repository;

/**
 * Заглушка под будущую интеграцию мессенджера MAX.
 * Сегодня всегда говорит "не поддерживаю" — но архитектурно занимает
 * правильное место (адаптер канала), чтобы включение свелось к добавлению
 * HTTP-вызова, без изменения вызывающего кода.
 *
 * @see config/guardreviews.php — max.enabled
 */
final readonly class MaxNotificationChannel implements NotificationChannel
{
    public function __construct(
        private Repository $config,
    ) {}

    public function supports(OwnerNotification $notification): bool
    {
        return $this->isEnabled()
            && $notification->contact->maxId !== null;
    }

    public function deliver(OwnerNotification $notification): void
    {
        // MAX integration is not enabled yet — оставлено как явная точка расширения.
    }

    private function isEnabled(): bool
    {
        return (bool) $this->config->get('guardreviews.max.enabled', false);
    }
}
