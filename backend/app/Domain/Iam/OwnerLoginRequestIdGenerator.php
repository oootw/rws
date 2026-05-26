<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerLoginRequestIdGenerator
{
    public function next(): OwnerLoginRequestId;
}
