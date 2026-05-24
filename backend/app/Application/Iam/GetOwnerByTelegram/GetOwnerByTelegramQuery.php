<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerByTelegram;

final readonly class GetOwnerByTelegramQuery
{
    public function __construct(public string $telegramId) {}
}
