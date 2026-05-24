<?php

declare(strict_types=1);

namespace App\Application\Analytics\RecordAction;

use App\Domain\Analytics\ActionType;

final readonly class RecordActionCommand
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $placeId,
        public ActionType $type,
        public ?array $metadata = null,
    ) {}
}
