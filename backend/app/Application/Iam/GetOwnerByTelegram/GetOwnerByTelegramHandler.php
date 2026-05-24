<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerByTelegram;

use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\TelegramId;

final readonly class GetOwnerByTelegramHandler
{
    public function __construct(
        private OwnerRepository $owners,
    ) {}

    public function handle(GetOwnerByTelegramQuery $query): ?Owner
    {
        return $this->owners->findByTelegramId(new TelegramId($query->telegramId));
    }
}
