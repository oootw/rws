<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics;

use App\Domain\Analytics\ActionLog as DomainActionLog;
use App\Models\ActionLog as ActionLogModel;

final class ActionLogMapper
{
    public function toPersistence(DomainActionLog $log, ActionLogModel $model): ActionLogModel
    {
        $model->id = $log->id->value;
        $model->place_id = $log->placeId->value;
        $model->action_type = $log->type;
        $model->metadata = $log->metadata;
        $model->created_at = $log->recordedAt;

        return $model;
    }
}
