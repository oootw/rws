<?php

declare(strict_types=1);

namespace App\Application\Reviews\SubmitReview;

/**
 * Иммутабельный ввод для use case "оставить негативный отзыв".
 * Уже прошёл проверку формата (FormRequest) и капчу.
 * IP — в виде готового хеша, чтобы домен не зависел от способа хеширования.
 */
final readonly class SubmitReviewCommand
{
    public function __construct(
        public string $placeId,
        public int $stars,
        public string $text,
        public string $contact,
        public ?string $ipHash,
    ) {}
}
