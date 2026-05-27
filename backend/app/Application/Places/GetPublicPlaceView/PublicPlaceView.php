<?php

declare(strict_types=1);

namespace App\Application\Places\GetPublicPlaceView;

/**
 * Read-model для публичной формы отзыва: то, что нужно фронту, и ничего
 * лишнего. HTTP-слой переводит эту DTO в JSON один в один.
 *
 * `tariffFeatures` — только scan-relevant фичи владельца (см.
 * {@see GetPublicPlaceViewHandler::SCAN_FEATURES}). Полный список фич
 * сюда не отдаём — это утечка деталей биллинга на публичный endpoint.
 */
final readonly class PublicPlaceView
{
    /**
     * @param  list<array{type: string, url: string, label: string}>  $platforms
     * @param  list<string>  $tariffFeatures
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $backgroundImageUrl,
        public array $platforms,
        public array $tariffFeatures = [],
    ) {}
}
