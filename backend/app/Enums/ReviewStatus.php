<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Archived = 'archived';
}
