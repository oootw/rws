<?php

declare(strict_types=1);

namespace App\Domain\Admin;

interface AdminActionLogRepository
{
    public function save(AdminActionLog $log): void;
}
