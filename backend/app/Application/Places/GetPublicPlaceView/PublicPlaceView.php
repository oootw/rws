<?php

declare(strict_types=1);

namespace App\Application\Places\GetPublicPlaceView;

/**
 * Read-model для публичной формы отзыва: то, что нужно фронту, и ничего
 * лишнего. HTTP-слой переводит эту DTO в JSON один в один.
 */
final readonly class PublicPlaceView
{
    /**
     * @param  list<array{type: string, url: string, label: string}>  $platforms
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $backgroundImageUrl,
        public array $platforms,
    ) {}
}
