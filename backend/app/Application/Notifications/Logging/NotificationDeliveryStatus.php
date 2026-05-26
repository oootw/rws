<?php

declare(strict_types=1);

namespace App\Application\Notifications\Logging;

enum NotificationDeliveryStatus: string
{
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
