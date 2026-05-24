<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListRecentReviewsForOwner;

use App\Enums\ReviewStatus;

/**
 * Read-model «строчка списка отзывов в личном кабинете».
 * Только данные для отображения; Reviews aggregate сюда не вылезает.
 */
final readonly class RecentReviewView
{
    public function __construct(
        public string $id,
        public string $placeTitle,
        public int $stars,
        public ReviewStatus $status,
        public string $contact,
        public string $text,
    ) {}
}
