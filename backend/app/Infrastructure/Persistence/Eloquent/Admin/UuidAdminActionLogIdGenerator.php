<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Admin;

use App\Domain\Admin\AdminActionLogId;
use App\Domain\Admin\AdminActionLogIdGenerator;
use Illuminate\Support\Str;

final class UuidAdminActionLogIdGenerator implements AdminActionLogIdGenerator
{
    public function next(): AdminActionLogId
    {
        return new AdminActionLogId((string) Str::uuid());
    }
}
