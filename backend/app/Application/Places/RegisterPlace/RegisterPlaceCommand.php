<?php

declare(strict_types=1);

namespace App\Application\Places\RegisterPlace;

/**
 * Сырой ввод от создания точки (Telegram conversation сегодня, форма завтра).
 *
 * platforms — список как пришло с интерфейса: type/url/label.
 * Валидация форматов VO и фильтрация пустых URL — забота use case'а.
 *
 * @phpstan-type RawPlatform array{type: string, url: string, label: string}
 */
final readonly class RegisterPlaceCommand
{
    /**
     * @param  list<RawPlatform>  $platforms
     */
    public function __construct(
        public string $ownerId,
        public string $title,
        public array $platforms,
        public ?string $backgroundImageUrl,
    ) {}
}
