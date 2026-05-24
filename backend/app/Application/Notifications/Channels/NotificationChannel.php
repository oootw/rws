<?php

declare(strict_types=1);

namespace App\Application\Notifications\Channels;

use App\Application\Notifications\OwnerNotification;

/**
 * Один способ доставки (Telegram, MAX, e-mail).
 *
 * supports() — может ли канал доставить *именно это* уведомление сейчас
 * (есть ли у владельца идентификатор + сконфигурирован ли канал).
 * deliver() — собственно отправка. Поднимать исключения наружу не должен.
 */
interface NotificationChannel
{
    public function supports(OwnerNotification $notification): bool;

    public function deliver(OwnerNotification $notification): void;
}
