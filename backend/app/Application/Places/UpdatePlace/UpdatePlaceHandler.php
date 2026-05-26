<?php

declare(strict_types=1);

namespace App\Application\Places\UpdatePlace;

use App\Application\Places\Exceptions\PlaceNotFound;
use App\Application\Places\Support\PlatformsBuilder;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;
use App\Domain\Places\Title;

/**
 * Use case: админ (или будущий ЛК владельца) меняет профиль точки —
 * заголовок, набор площадок, обложку. Активация/деактивация и удаление
 * вынесены в отдельные use cases, чтобы не размывать ответственность.
 */
final readonly class UpdatePlaceHandler
{
    public function __construct(
        private PlaceRepository $places,
        private PlatformsBuilder $platforms,
    ) {}

    public function handle(UpdatePlaceCommand $command): void
    {
        $place = $this->places->findById(new PlaceId($command->placeId));

        if ($place === null) {
            throw new PlaceNotFound;
        }

        $place->updateProfile(
            title: new Title($command->title),
            platforms: $this->platforms->build($command->platforms),
            backgroundImageUrl: $this->platforms->normalize($command->backgroundImageUrl),
        );

        $this->places->save($place);
    }
}
