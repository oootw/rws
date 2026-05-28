<?php

declare(strict_types=1);

namespace App\Domain\Iam\Events;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\TelegramChatId;
use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

final readonly class OwnerTelegramChatLinked implements DomainEvent
{
    public function __construct(
        public OwnerTelegramChatId $id,
        public OwnerId $ownerId,
        public TelegramChatId $chatId,
        public DateTimeImmutable $linkedAt,
    ) {}
}
