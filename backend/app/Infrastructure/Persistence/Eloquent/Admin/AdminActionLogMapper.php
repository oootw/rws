<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Admin;

use App\Domain\Admin\AdminActionLog as DomainAdminActionLog;
use App\Models\AdminActionLog as AdminActionLogModel;

final class AdminActionLogMapper
{
    public function toPersistence(DomainAdminActionLog $log, AdminActionLogModel $model): AdminActionLogModel
    {
        $model->id = $log->id->value;
        $model->admin_email = $log->adminEmail;
        $model->action = $log->action;
        $model->resource = $log->resource;
        $model->record_id = $log->recordId;
        $model->payload = $log->payload;
        $model->ip = $log->ip;
        $model->user_agent = $log->userAgent;
        $model->created_at = $log->recordedAt;

        return $model;
    }
}
