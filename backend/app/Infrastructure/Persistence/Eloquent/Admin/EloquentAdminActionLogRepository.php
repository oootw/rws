<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Admin;

use App\Domain\Admin\AdminActionLog;
use App\Domain\Admin\AdminActionLogRepository;
use App\Models\AdminActionLog as AdminActionLogModel;

final readonly class EloquentAdminActionLogRepository implements AdminActionLogRepository
{
    public function __construct(
        private AdminActionLogMapper $mapper,
    ) {}

    public function save(AdminActionLog $log): void
    {
        $this->mapper->toPersistence($log, new AdminActionLogModel)->save();
    }
}
