<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerIdGenerator;
use Illuminate\Support\Str;

final class UuidOwnerIdGenerator implements OwnerIdGenerator
{
    public function next(): OwnerId
    {
        return new OwnerId((string) Str::uuid());
    }
}
