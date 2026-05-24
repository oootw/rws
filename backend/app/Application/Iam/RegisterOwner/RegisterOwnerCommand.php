<?php

declare(strict_types=1);

namespace App\Application\Iam\RegisterOwner;

final readonly class RegisterOwnerCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $subdomain,
        public ?string $telegramId,
    ) {}
}
