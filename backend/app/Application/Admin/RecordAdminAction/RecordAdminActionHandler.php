<?php

declare(strict_types=1);

namespace App\Application\Admin\RecordAdminAction;

use App\Domain\Admin\AdminActionLog;
use App\Domain\Admin\AdminActionLogIdGenerator;
use App\Domain\Admin\AdminActionLogRepository;
use App\Domain\Shared\Clock\Clock;

final readonly class RecordAdminActionHandler
{
    public function __construct(
        private AdminActionLogRepository $logs,
        private AdminActionLogIdGenerator $ids,
        private Clock $clock,
    ) {}

    public function handle(RecordAdminActionCommand $command): void
    {
        $this->logs->save(new AdminActionLog(
            id: $this->ids->next(),
            adminEmail: $command->adminEmail,
            action: $command->action,
            resource: $command->resource,
            recordId: $command->recordId,
            payload: $command->payload,
            ip: $command->ip,
            userAgent: $command->userAgent,
            recordedAt: $this->clock->now(),
        ));
    }
}
