<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerIdGenerator
{
    public function next(): OwnerId;
}
