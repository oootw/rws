<?php

declare(strict_types=1);

namespace App\Application\Places\RegisterPlace;

use App\Application\Places\Support\PlatformsBuilder;
use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceIdGenerator;
use App\Domain\Places\PlaceRepository;
use App\Domain\Places\Title;

/**
 * Use case: владелец создал точку.
 * Возвращает ID — интерфейсу обычно нужно показать "точка X создана".
 */
final readonly class RegisterPlaceHandler
{
    public function __construct(
        private PlaceRepository $places,
        private PlaceIdGenerator $idGenerator,
        private PlatformsBuilder $platforms,
    ) {}

    public function handle(RegisterPlaceCommand $command): PlaceId
    {
        $place = Place::register(
            id: $this->idGenerator->next(),
            ownerId: new OwnerId($command->ownerId),
            title: new Title($command->title),
            platforms: $this->platforms->build($command->platforms),
            backgroundImageUrl: $this->platforms->normalize($command->backgroundImageUrl),
        );

        $this->places->save($place);

        return $place->id;
    }
}
