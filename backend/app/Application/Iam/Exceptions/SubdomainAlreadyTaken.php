<?php

declare(strict_types=1);

namespace App\Application\Iam\Exceptions;

use RuntimeException;

final class SubdomainAlreadyTaken extends RuntimeException
{
    public function __construct(public readonly string $slug)
    {
        parent::__construct("Адрес «{$slug}» уже занят.");
    }
}
