<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics;

use App\Domain\Analytics\ActionLogId;
use App\Domain\Analytics\ActionLogIdGenerator;
use Illuminate\Support\Str;

final class UuidActionLogIdGenerator implements ActionLogIdGenerator
{
    public function next(): ActionLogId
    {
        return new ActionLogId((string) Str::uuid());
    }
}
