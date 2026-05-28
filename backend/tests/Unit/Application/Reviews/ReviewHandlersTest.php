<?php

declare(strict_types=1);

use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusCommand;
use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusHandler;
use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusResult;
use App\Application\Reviews\ListRecentReviewsForOwner\ListRecentReviewsForOwnerHandler;
use App\Application\Reviews\ListRecentReviewsForOwner\ListRecentReviewsForOwnerQuery;
use App\Application\Reviews\ListRecentReviewsForOwner\RecentReviewsReader;
use App\Application\Reviews\ListRecentReviewsForOwner\RecentReviewView;
use App\Enums\ReviewStatus;

it('меняет статус отзыва владельца', function (): void {
    $place = activePlace();
    $review = restoredReview(placeId: $place->id->value);
    $reviews = fakeReviewsRepository([$review]);
    $places = fakePlacesRepository([$place]);

    $result = (new ChangeReviewStatusHandler($reviews, $places))->handle(
        new ChangeReviewStatusCommand(
            reviewId: '11111111-1111-1111-1111-111111111111',
            ownerId: '22222222-2222-2222-2222-222222222222',
            newStatus: ReviewStatus::InProgress,
        ),
    );

    expect($result)->toBe(ChangeReviewStatusResult::Updated)
        ->and($reviews->reviews[0]->status())->toBe(ReviewStatus::InProgress);
});

it('возвращает «отзыв не найден», если отзыва нет', function (): void {
    $result = (new ChangeReviewStatusHandler(fakeReviewsRepository(), fakePlacesRepository([activePlace()])))
        ->handle(new ChangeReviewStatusCommand(
            reviewId: '11111111-1111-1111-1111-111111111111',
            ownerId: '22222222-2222-2222-2222-222222222222',
            newStatus: ReviewStatus::Resolved,
        ));

    expect($result)->toBe(ChangeReviewStatusResult::ReviewNotFound);
});

it('возвращает «не принадлежит вызывающему» для чужой точки', function (): void {
    $review = restoredReview(placeId: '99999999-9999-9999-9999-999999999999');

    $result = (new ChangeReviewStatusHandler(
        fakeReviewsRepository([$review]),
        fakePlacesRepository([activePlace()]),
    ))->handle(new ChangeReviewStatusCommand(
        reviewId: '11111111-1111-1111-1111-111111111111',
        ownerId: '22222222-2222-2222-2222-222222222222',
        newStatus: ReviewStatus::Archived,
    ));

    expect($result)->toBe(ChangeReviewStatusResult::NotOwnedByCaller);
});

it('возвращает последние отзывы владельца через ридер', function (): void {
    $reader = new class implements RecentReviewsReader
    {
        public ?string $ownerId = null;

        public ?int $limit = null;

        public function recentForOwner(string $ownerId, int $limit): array
        {
            $this->ownerId = $ownerId;
            $this->limit = $limit;

            return [
                new RecentReviewView(
                    id: '11111111-1111-1111-1111-111111111111',
                    placeTitle: 'Кафе Уют',
                    stars: 2,
                    status: ReviewStatus::New,
                    contact: 'test@example.com',
                    text: 'Долго ждали',
                ),
            ];
        }
    };

    $views = (new ListRecentReviewsForOwnerHandler($reader))->handle(
        new ListRecentReviewsForOwnerQuery(
            ownerId: '22222222-2222-2222-2222-222222222222',
            limit: 5,
        ),
    );

    expect($reader->ownerId)->toBe('22222222-2222-2222-2222-222222222222')
        ->and($reader->limit)->toBe(5)
        ->and($views)->toHaveCount(1)
        ->and($views[0]->placeTitle)->toBe('Кафе Уют');
});
