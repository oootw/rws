<?php

declare(strict_types=1);

namespace App\Application\Notifications;

/**
 * Кнопка-действие, которую владелец видит вместе с уведомлением.
 * Каналы, поддерживающие интерактив (Telegram), рендерят её в inline-кнопку.
 * Каналы без интерактива (e-mail) — игнорируют.
 *
 * payload — внутренний идентификатор действия (например, "review:{id}:resolved");
 * формат интерпретирует канал.
 */
final readonly class NotificationAction
{
    public function __construct(
        public string $label,
        public string $payload,
    ) {}
}
