<?php

declare(strict_types=1);

namespace App\Application\Reviews\ChangeReviewStatus;

enum ChangeReviewStatusResult
{
    case Updated;
    case ReviewNotFound;
    case NotOwnedByCaller;
}
