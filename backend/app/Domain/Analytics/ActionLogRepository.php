<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

interface ActionLogRepository
{
    public function save(ActionLog $log): void;
}
