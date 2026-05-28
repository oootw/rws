<?php

declare(strict_types=1);

namespace App\Application\Notifications\Channels;

use App\Domain\Notifications\PushSubscriptionView;

/**
 * Порт для отправки одного Web Push сообщения.
 * Реализация скрывает VAPID-подпись и HTTP к push-сервисам.
 * Канал доставки (WebPushNotificationChannel) использует этот порт
 * и обрабатывает результат — gone-подписки удаляет, ошибки логирует.
 */
interface WebPushClient
{
    public function send(PushSubscriptionView $subscription, string $payload): WebPushSendResult;
}
