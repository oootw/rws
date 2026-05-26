<?php

declare(strict_types=1);

namespace App\Application\Iam\RequestOwnerLogin;

final readonly class RequestOwnerLoginCommand
{
    public function __construct(public string $telegramId) {}
}
