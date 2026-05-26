<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

enum ActionType: string
{
    case Scanned = 'scanned';
    case LeftNegative = 'left_negative';
    case RedirectedExternal = 'redirected_external';
    case AdminDeletedReview = 'admin_deleted_review';
}
