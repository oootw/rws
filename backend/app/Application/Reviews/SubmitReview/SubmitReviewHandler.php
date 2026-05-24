<?php

declare(strict_types=1);

namespace App\Application\Reviews\SubmitReview;

use App\Application\Shared\Events\DomainEventDispatcher;
use App\Domain\Places\PlaceId;
use App\Domain\Reviews\ContactInfo;
use App\Domain\Reviews\Review;
use App\Domain\Reviews\ReviewIdGenerator;
use App\Domain\Reviews\ReviewRepository;
use App\Domain\Reviews\ReviewText;
use App\Domain\Reviews\Stars;
use App\Domain\Shared\Clock\Clock;
use App\Domain\Shared\Identity\IpHash;

/**
 * Use case: посетитель оставил негативный отзыв.
 *
 * Шаги:
 *  1) Собрать агрегат Review из value objects.
 *  2) Сохранить через репозиторий.
 *  3) Опубликовать накопленные доменные события (слушатели
 *     отвечают за журнал действий и уведомления владельцу).
 */
final readonly class SubmitReviewHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private ReviewIdGenerator $idGenerator,
        private Clock $clock,
        private DomainEventDispatcher $events,
    ) {}

    public function handle(SubmitReviewCommand $command): void
    {
        $review = Review::submit(
            id: $this->idGenerator->next(),
            placeId: new PlaceId($command->placeId),
            stars: new Stars($command->stars),
            text: new ReviewText($command->text),
            contact: new ContactInfo($command->contact),
            ipHash: IpHash::fromHashed($command->ipHash),
            submittedAt: $this->clock->now(),
        );

        $this->reviews->save($review);
        $this->events->dispatchAll($review->pullRecordedEvents());
    }
}
