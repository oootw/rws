<?php

declare(strict_types=1);

namespace App\Domain\Reviews;

use App\Domain\Places\PlaceId;
use App\Domain\Reviews\Events\NegativeReviewSubmitted;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\Identity\IpHash;
use App\Enums\ReviewStatus;
use DateTimeImmutable;
use DomainException;

/**
 * Aggregate root контекста Reviews.
 *
 * Инкапсулирует:
 *  - инвариант "негативный отзыв = stars <= 3"
 *  - переходы статуса (new → in_progress → resolved/archived)
 *  - запись доменных событий, которые порождают побочные эффекты
 *    (уведомления, журнал) уже на уровне Application.
 */
final class Review
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    private function __construct(
        public readonly ReviewId $id,
        public readonly PlaceId $placeId,
        public readonly Stars $stars,
        public readonly ReviewText $text,
        public readonly ContactInfo $contact,
        public readonly ?IpHash $ipHash,
        public readonly DateTimeImmutable $submittedAt,
        private ReviewStatus $status,
    ) {}

    public static function submit(
        ReviewId $id,
        PlaceId $placeId,
        Stars $stars,
        ReviewText $text,
        ContactInfo $contact,
        ?IpHash $ipHash,
        DateTimeImmutable $submittedAt,
    ): self {
        if (! $stars->isNegative()) {
            throw new DomainException(
                'Через форму обратной связи принимаются только негативные отзывы (1-3 звезды).'
            );
        }

        $review = new self(
            id: $id,
            placeId: $placeId,
            stars: $stars,
            text: $text,
            contact: $contact,
            ipHash: $ipHash,
            submittedAt: $submittedAt,
            status: ReviewStatus::New,
        );

        $review->record(new NegativeReviewSubmitted($id, $placeId, $submittedAt));

        return $review;
    }

    public static function restore(
        ReviewId $id,
        PlaceId $placeId,
        Stars $stars,
        ReviewText $text,
        ContactInfo $contact,
        ?IpHash $ipHash,
        DateTimeImmutable $submittedAt,
        ReviewStatus $status,
    ): self {
        return new self($id, $placeId, $stars, $text, $contact, $ipHash, $submittedAt, $status);
    }

    public function status(): ReviewStatus
    {
        return $this->status;
    }

    public function changeStatus(ReviewStatus $newStatus): void
    {
        $this->status = $newStatus;
    }

    /**
     * @return list<DomainEvent>
     */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
