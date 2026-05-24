<?php

declare(strict_types=1);

namespace App\Domain\Places;

use InvalidArgumentException;

/**
 * Конкретная площадка (2GIS / Яндекс / своя) с ссылкой и подписью кнопки.
 *
 * Сущность пустого URL быть не должна — фильтрация "конфигурированности"
 * сделана здесь, единожды.
 */
final readonly class PlatformLink
{
    public function __construct(
        public PlatformType $type,
        public string $url,
        public string $label,
    ) {
        if ($url === '') {
            throw new InvalidArgumentException('Ссылка на площадку не может быть пустой.');
        }

        if ($label === '') {
            throw new InvalidArgumentException('Подпись площадки не может быть пустой.');
        }
    }
}
