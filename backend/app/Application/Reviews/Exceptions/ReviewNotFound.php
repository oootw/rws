<?php

declare(strict_types=1);

namespace App\Application\Reviews\Exceptions;

use RuntimeException;

/**
 * Бросается, когда use case (например, удаление из админки) не находит
 * запрошенный отзыв — параллельное удаление или устаревший id.
 */
final class ReviewNotFound extends RuntimeException {}
