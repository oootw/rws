<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

use InvalidArgumentException;

/**
 * Read-side представление push-подписки для уведомлений: только то,
 * что нужно каналу доставки (endpoint + ключи). Без id, owner_id, user_agent.
 */
final readonly class PushSubscriptionView
{
    public function __construct(
        public string $endpoint,
        public string $p256dh,
        public string $auth,
    ) {
        if ($endpoint === '') {
            throw new InvalidArgumentException('PushSubscriptionView.endpoint не может быть пустым.');
        }

        if ($p256dh === '') {
            throw new InvalidArgumentException('PushSubscriptionView.p256dh не может быть пустым.');
        }

        if ($auth === '') {
            throw new InvalidArgumentException('PushSubscriptionView.auth не может быть пустым.');
        }
    }
}
