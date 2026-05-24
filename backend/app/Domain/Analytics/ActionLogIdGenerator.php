<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

interface ActionLogIdGenerator
{
    public function next(): ActionLogId;
}
