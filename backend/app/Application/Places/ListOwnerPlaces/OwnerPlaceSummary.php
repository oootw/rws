<?php

declare(strict_types=1);

namespace App\Application\Places\ListOwnerPlaces;

/**
 * Сжатая карточка точки для списков (бот, личный кабинет).
 */
final readonly class OwnerPlaceSummary
{
    public function __construct(
        public string $id,
        public string $title,
        public int $platformCount,
        public bool $isActive,
    ) {}
}
