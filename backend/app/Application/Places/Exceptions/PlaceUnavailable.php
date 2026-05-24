<?php

declare(strict_types=1);

namespace App\Application\Places\Exceptions;

use RuntimeException;

/**
 * Бросаем, когда публичная страница точки не должна быть отдана:
 * точки нет, она выключена или не принадлежит тенанту.
 * HTTP-слой ловит это и отдаёт стандартный 404 place_not_found,
 * не раскрывая, по какой именно причине.
 */
final class PlaceUnavailable extends RuntimeException {}
