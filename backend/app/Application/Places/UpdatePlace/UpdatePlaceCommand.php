<?php

declare(strict_types=1);

namespace App\Application\Places\UpdatePlace;

/**
 * Команда обновления профиля точки. Структура platforms повторяет
 * формат, который принимает RegisterPlaceCommand — взаимозаменяемые
 * сырые данные с интерфейса (Filament repeater / Telegram conversation).
 *
 * @phpstan-type RawPlatform array{type: string, url: string, label: string}
 */
final readonly class UpdatePlaceCommand
{
    /**
     * @param  list<RawPlatform>  $platforms
     */
    public function __construct(
        public string $placeId,
        public string $title,
        public array $platforms,
        public ?string $backgroundImageUrl,
    ) {}
}
