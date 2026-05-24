<?php

declare(strict_types=1);

namespace App\Domain\Places;

enum PlatformType: string
{
    case TwoGis = '2gis';
    case Yandex = 'yandex';
    case Custom = 'custom';
}
