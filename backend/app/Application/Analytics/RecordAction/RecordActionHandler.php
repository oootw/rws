<?php

declare(strict_types=1);

namespace App\Application\Analytics\RecordAction;

use App\Domain\Analytics\ActionLog;
use App\Domain\Analytics\ActionLogIdGenerator;
use App\Domain\Analytics\ActionLogRepository;
use App\Domain\Places\PlaceId;
use App\Domain\Shared\Clock\Clock;

/**
 * Use case: зафиксировать факт действия посетителя на странице точки.
 * Используется и публичным API (скан/редирект), и слушателями домена (негатив).
 */
final readonly class RecordActionHandler
{
    public function __construct(
        private ActionLogRepository $logs,
        private ActionLogIdGenerator $idGenerator,
        private Clock $clock,
    ) {}

    public function handle(RecordActionCommand $command): void
    {
        $this->logs->save(new ActionLog(
            id: $this->idGenerator->next(),
            placeId: new PlaceId($command->placeId),
            type: $command->type,
            metadata: $command->metadata,
            recordedAt: $this->clock->now(),
        ));
    }
}
