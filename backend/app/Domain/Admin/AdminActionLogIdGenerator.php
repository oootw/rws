<?php

declare(strict_types=1);

namespace App\Domain\Admin;

interface AdminActionLogIdGenerator
{
    public function next(): AdminActionLogId;
}
