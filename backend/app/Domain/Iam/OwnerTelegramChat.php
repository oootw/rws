<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use App\Domain\Iam\Events\OwnerTelegramChatLinked;
use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Aggregate root: привязка группового Telegram-чата к владельцу.
 *
 * Инвариант «уникальность (owner_id, chat_id)» обеспечивается на уровне
 * репозитория и БД (unique-ограничение). Сам агрегат лишь хранит снимок
 * привязки и переименование (title).
 */
final class OwnerTelegramChat
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    private function __construct(
        public readonly OwnerTelegramChatId $id,
        public readonly OwnerId $ownerId,
        public readonly TelegramChatId $chatId,
        private ?string $title,
        public readonly DateTimeImmutable $linkedAt,
    ) {}

    public static function link(
        OwnerTelegramChatId $id,
        OwnerId $ownerId,
        TelegramChatId $chatId,
        ?string $title,
        DateTimeImmutable $linkedAt,
    ): self {
        $chat = new self($id, $ownerId, $chatId, $title, $linkedAt);
        $chat->record(new OwnerTelegramChatLinked($id, $ownerId, $chatId, $linkedAt));

        return $chat;
    }

    public static function restore(
        OwnerTelegramChatId $id,
        OwnerId $ownerId,
        TelegramChatId $chatId,
        ?string $title,
        DateTimeImmutable $linkedAt,
    ): self {
        return new self($id, $ownerId, $chatId, $title, $linkedAt);
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function rename(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return list<DomainEvent>
     */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
