<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Channels;

use RuntimeException;

final class TelegramDeliveryFailed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Telegram: ни один таргет (DM + групповые чаты) не принял уведомление.');
    }
}
