<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Reviews;

use App\Domain\Places\PlaceId;
use App\Domain\Reviews\ContactInfo;
use App\Domain\Reviews\Review;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewText;
use App\Domain\Reviews\Stars;
use App\Domain\Shared\Identity\IpHash;
use App\Models\Review as ReviewModel;
use DateTimeImmutable;

/**
 * Переводит доменный агрегат Review в Eloquent-модель и обратно.
 * Это единственное место, где код домена встречается с ORM.
 */
final class ReviewMapper
{
    public function toPersistence(Review $review, ReviewModel $model): ReviewModel
    {
        $model->id = $review->id->value;
        $model->place_id = $review->placeId->value;
        $model->stars = $review->stars->value;
        $model->text = $review->text->value;
        $model->contact = $review->contact->value;
        $model->ip_hash = $review->ipHash?->value;
        $model->status = $review->status();
        $model->created_at ??= $review->submittedAt;
        $model->updated_at = $review->submittedAt;

        return $model;
    }

    public function toDomain(ReviewModel $model): Review
    {
        return Review::restore(
            id: new ReviewId((string) $model->id),
            placeId: new PlaceId((string) $model->place_id),
            stars: new Stars((int) $model->stars),
            text: new ReviewText((string) $model->text),
            contact: new ContactInfo((string) $model->contact),
            ipHash: IpHash::fromHashed($model->ip_hash),
            submittedAt: DateTimeImmutable::createFromInterface($model->created_at),
            status: $model->status,
        );
    }
}
