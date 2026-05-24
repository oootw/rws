<?php

declare(strict_types=1);

namespace App\Application\Payments;

enum NotificationOutcome
{
    case Confirmed;
    case Rejected;
    case Pending;
}
