<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use RuntimeException;

final class NotificationDeliveryFailed extends RuntimeException
{
    /**
     * @param  list<array{channel: class-string, error: string}>  $channelErrors
     */
    public function __construct(
        string $message,
        public readonly array $channelErrors,
    ) {
        $details = [];

        foreach ($channelErrors as $error) {
            $details[] = sprintf('%s: %s', $error['channel'], $error['error']);
        }

        parent::__construct(
            $details === []
                ? $message
                : $message.' Подробно: '.implode('; ', $details),
        );
    }
}
