<?php

declare(strict_types=1);

namespace App\Application\Places\DeletePlace;

use App\Application\Places\Exceptions\PlaceNotFound;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;

/**
 * Use case: админ удаляет точку. Каскадно убираются reviews и action_logs
 * по FK ON DELETE CASCADE в миграциях — это решение фиксируется здесь
 * для будущих читателей, а не дублируется ручным удалением.
 */
final readonly class DeletePlaceHandler
{
    public function __construct(
        private PlaceRepository $places,
    ) {}

    public function handle(DeletePlaceCommand $command): void
    {
        $id = new PlaceId($command->placeId);

        if ($this->places->findById($id) === null) {
            throw new PlaceNotFound;
        }

        $this->places->delete($id);
    }
}
