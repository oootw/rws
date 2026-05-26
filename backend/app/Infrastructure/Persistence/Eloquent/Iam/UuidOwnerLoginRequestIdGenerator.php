<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerLoginRequestId;
use App\Domain\Iam\OwnerLoginRequestIdGenerator;
use Illuminate\Support\Str;

final class UuidOwnerLoginRequestIdGenerator implements OwnerLoginRequestIdGenerator
{
    public function next(): OwnerLoginRequestId
    {
        return new OwnerLoginRequestId((string) Str::uuid());
    }
}
