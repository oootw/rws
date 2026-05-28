<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Channels;

use RuntimeException;

final class WebPushDeliveryFailed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Web Push: ни одна живая подписка не приняла уведомление.');
    }
}
