<?php

declare(strict_types=1);

namespace App\Domain\Places;

use App\Domain\Iam\OwnerId;

/**
 * Aggregate root контекста Places.
 *
 * Что инкапсулирует:
 *  - связку с владельцем (OwnerId — cross-aggregate ID, не загружаем User);
 *  - правило "точка должна быть активной", чтобы публичные сценарии
 *    (сканирование/редирект/отзыв) могли уверенно опираться на флаг;
 *  - набор настроенных площадок (PlatformLink) и поиск по типу — это
 *    нужно редиректу.
 */
final class Place
{
    /**
     * @param  list<PlatformLink>  $platforms
     */
    private function __construct(
        public readonly PlaceId $id,
        public readonly OwnerId $ownerId,
        private Title $title,
        private array $platforms,
        private ?string $backgroundImageUrl,
        private bool $isActive,
    ) {}

    /**
     * @param  list<PlatformLink>  $platforms
     */
    public static function register(
        PlaceId $id,
        OwnerId $ownerId,
        Title $title,
        array $platforms,
        ?string $backgroundImageUrl,
    ): self {
        return new self($id, $ownerId, $title, $platforms, $backgroundImageUrl, isActive: true);
    }

    /**
     * @param  list<PlatformLink>  $platforms
     */
    public static function restore(
        PlaceId $id,
        OwnerId $ownerId,
        Title $title,
        array $platforms,
        ?string $backgroundImageUrl,
        bool $isActive,
    ): self {
        return new self($id, $ownerId, $title, $platforms, $backgroundImageUrl, $isActive);
    }

    public function title(): Title
    {
        return $this->title;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function backgroundImageUrl(): ?string
    {
        return $this->backgroundImageUrl;
    }

    public function isOwnedBy(OwnerId $ownerId): bool
    {
        return $this->ownerId->equals($ownerId);
    }

    /**
     * @return list<PlatformLink>
     */
    public function platforms(): array
    {
        return $this->platforms;
    }

    public function platform(PlatformType $type): ?PlatformLink
    {
        foreach ($this->platforms as $platform) {
            if ($platform->type === $type) {
                return $platform;
            }
        }

        return null;
    }

    public function hasConfiguredPlatforms(): bool
    {
        return $this->platforms !== [];
    }
}
