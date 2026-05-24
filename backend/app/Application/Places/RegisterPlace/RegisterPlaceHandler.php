<?php

declare(strict_types=1);

namespace App\Application\Places\RegisterPlace;

use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceIdGenerator;
use App\Domain\Places\PlaceRepository;
use App\Domain\Places\PlatformLink;
use App\Domain\Places\PlatformType;
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
    ) {}

    public function handle(RegisterPlaceCommand $command): PlaceId
    {
        $place = Place::register(
            id: $this->idGenerator->next(),
            ownerId: new OwnerId($command->ownerId),
            title: new Title($command->title),
            platforms: $this->buildPlatforms($command->platforms),
            backgroundImageUrl: $this->normalizeOptional($command->backgroundImageUrl),
        );

        $this->places->save($place);

        return $place->id;
    }

    /**
     * @param  list<array{type: string, url: string, label: string}>  $raw
     * @return list<PlatformLink>
     */
    private function buildPlatforms(array $raw): array
    {
        $platforms = [];

        foreach ($raw as $entry) {
            $url = $this->normalizeOptional($entry['url'] ?? null);

            if ($url === null) {
                continue;
            }

            $platforms[] = new PlatformLink(
                type: PlatformType::from($entry['type']),
                url: $url,
                label: $entry['label'],
            );
        }

        return $platforms;
    }

    private function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
