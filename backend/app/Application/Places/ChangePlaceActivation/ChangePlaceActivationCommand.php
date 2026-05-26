<?php

declare(strict_types=1);

namespace App\Application\Places\ChangePlaceActivation;

/**
 * Команда переключения активности точки. Один use case, две инструкции —
 * нагляднее, чем заводить отдельные ActivatePlace/DeactivatePlace, при том
 * что доменное поведение симметрично.
 */
final readonly class ChangePlaceActivationCommand
{
    public function __construct(
        public string $placeId,
        public bool $active,
    ) {}
}
