<?php

declare(strict_types=1);

namespace App\Application\Notifications\Channels;

/**
 * Результат одной попытки доставки Web Push.
 *
 *  - delivered: push-сервис принял уведомление (2xx).
 *  - gone: подписка мертва (404/410) — её надо удалить из БД.
 *
 * Сетевые/5xx ошибки → delivered=false, gone=false (повторим позже неявно
 * при следующем уведомлении; ретраев на уровне канала не делаем — KISS).
 */
final readonly class WebPushSendResult
{
    public function __construct(
        public bool $delivered,
        public bool $gone,
    ) {}

    public static function delivered(): self
    {
        return new self(delivered: true, gone: false);
    }

    public static function gone(): self
    {
        return new self(delivered: false, gone: true);
    }

    public static function failed(): self
    {
        return new self(delivered: false, gone: false);
    }
}
