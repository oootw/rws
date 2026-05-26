<?php

declare(strict_types=1);

namespace App\Application\Places\Support;

use App\Domain\Places\PlatformLink;
use App\Domain\Places\PlatformType;

/**
 * Превращает сырые «строки из формы / Telegram conversation» в доменные
 * PlatformLink: чистит whitespace, выкидывает записи с пустыми URL.
 *
 * Сидит в Application/Places/Support, потому что одинаково нужна
 * RegisterPlaceHandler и UpdatePlaceHandler.
 */
final readonly class PlatformsBuilder
{
    /**
     * @param  list<array{type: string, url: string, label: string}>  $raw
     * @return list<PlatformLink>
     */
    public function build(array $raw): array
    {
        $platforms = [];

        foreach ($raw as $entry) {
            $url = $this->normalize($entry['url'] ?? null);

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

    public function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
