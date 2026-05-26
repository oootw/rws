<?php

declare(strict_types=1);

namespace App\Application\Places\ChangePlaceActivation;

use App\Application\Places\Exceptions\PlaceNotFound;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;

/**
 * Use case: включить или выключить точку. Выключенная точка перестаёт
 * отдаваться через публичные сценарии (см. ResolvePublicPlace), но данные
 * и история не удаляются — для возврата владельца / спорных случаев.
 */
final readonly class ChangePlaceActivationHandler
{
    public function __construct(
        private PlaceRepository $places,
    ) {}

    public function handle(ChangePlaceActivationCommand $command): void
    {
        $place = $this->places->findById(new PlaceId($command->placeId));

        if ($place === null) {
            throw new PlaceNotFound;
        }

        $command->active ? $place->activate() : $place->deactivate();

        $this->places->save($place);
    }
}
