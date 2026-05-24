<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Places\PlaceId;
use DateTimeImmutable;

/**
 * Запись о действии посетителя на странице точки:
 * скан QR, переход на внешнюю площадку, оставленный негативный отзыв.
 *
 * Это лог, а не агрегат с поведением — поэтому простая иммутабельная запись.
 */
final readonly class ActionLog
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public ActionLogId $id,
        public PlaceId $placeId,
        public ActionType $type,
        public ?array $metadata,
        public DateTimeImmutable $recordedAt,
    ) {}
}
