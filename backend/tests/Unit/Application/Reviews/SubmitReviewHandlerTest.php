<?php

declare(strict_types=1);

use App\Application\Reviews\SubmitReview\SubmitReviewCommand;
use App\Application\Reviews\SubmitReview\SubmitReviewHandler;
use App\Application\Shared\Events\DomainEventDispatcher;
use App\Domain\Reviews\Events\NegativeReviewSubmitted;
use App\Domain\Reviews\Review;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewIdGenerator;
use App\Domain\Reviews\ReviewRepository;
use App\Domain\Shared\Clock\Clock;
use App\Domain\Shared\Events\DomainEvent;

function fakeReviewsRepo(): ReviewRepository
{
    return new class implements ReviewRepository
    {
        public ?Review $saved = null;

        public function save(Review $review): void
        {
            $this->saved = $review;
        }

        public function findById(ReviewId $id): ?Review
        {
            return $this->saved !== null && $this->saved->id->equals($id) ? $this->saved : null;
        }
    };
}

function fakeIdGen(string $value): ReviewIdGenerator
{
    return new class($value) implements ReviewIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): ReviewId
        {
            return new ReviewId($this->value);
        }
    };
}

function frozenClock(string $at): Clock
{
    return new class($at) implements Clock
    {
        public function __construct(private string $at) {}

        public function now(): DateTimeImmutable
        {
            return new DateTimeImmutable($this->at);
        }
    };
}

function captureEvents(): DomainEventDispatcher
{
    return new class implements DomainEventDispatcher
    {
        /** @var list<DomainEvent> */
        public array $events = [];

        public function dispatchAll(iterable $events): void
        {
            foreach ($events as $event) {
                $this->events[] = $event;
            }
        }
    };
}

it('сохраняет агрегат и публикует доменное событие', function (): void {
    $repo = fakeReviewsRepo();
    $events = captureEvents();

    $handler = new SubmitReviewHandler(
        $repo,
        fakeIdGen('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        frozenClock('2026-05-24T10:00:00Z'),
        $events,
    );

    $handler->handle(new SubmitReviewCommand(
        placeId: 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
        stars: 2,
        text: 'Долго ждали',
        contact: 'test@example.com',
        ipHash: 'hashed',
    ));

    expect($repo->saved)->not->toBeNull()
        ->and($repo->saved->id->value)->toBe('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa')
        ->and($events->events)->toHaveCount(1)
        ->and($events->events[0])->toBeInstanceOf(NegativeReviewSubmitted::class);
});
