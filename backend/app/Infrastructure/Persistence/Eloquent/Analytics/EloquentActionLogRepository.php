<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics;

use App\Domain\Analytics\ActionLog;
use App\Domain\Analytics\ActionLogRepository;
use App\Models\ActionLog as ActionLogModel;

final readonly class EloquentActionLogRepository implements ActionLogRepository
{
    public function __construct(
        private ActionLogMapper $mapper,
    ) {}

    public function save(ActionLog $log): void
    {
        $this->mapper->toPersistence($log, new ActionLogModel)->save();
    }
}
