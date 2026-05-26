<?php

declare(strict_types=1);

namespace App\Application\Places\Exceptions;

use RuntimeException;

/**
 * Бросается use case'ами Places, когда запрашиваемая точка не существует
 * (удалена параллельно или указан неверный id). Интерфейс-слой ловит и
 * показывает понятное сообщение пользователю.
 */
final class PlaceNotFound extends RuntimeException {}
