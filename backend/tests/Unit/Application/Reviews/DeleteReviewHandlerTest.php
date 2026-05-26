<?php

declare(strict_types=1);

use App\Application\Analytics\RecordAction\RecordActionHandler;
use App\Application\Reviews\DeleteReview\DeleteReviewCommand;
use App\Application\Reviews\DeleteReview\DeleteReviewHandler;
use App\Application\Reviews\Exceptions\ReviewNotFound;
use App\Domain\Analytics\ActionLog;
use App\Domain\Analytics\ActionLogId;
use App\Domain\Analytics\ActionLogIdGenerator;
use App\Domain\Analytics\ActionLogRepository;
use App\Domain\Analytics\ActionType;

function fakeActionLogRepository(): ActionLogRepository
{
    return new class implements ActionLogRepository
    {
        /** @var list<ActionLog> */
        public array $logs = [];

        public function save(ActionLog $log): void
        {
            $this->logs[] = $log;
        }
    };
}

function recordActionHandlerWith(ActionLogRepository $logs): RecordActionHandler
{
    $ids = new class implements ActionLogIdGenerator
    {
        public function next(): ActionLogId
        {
            return new ActionLogId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
        }
    };

    return new RecordActionHandler($logs, $ids, frozenClockAt('2026-05-25T10:00:00Z'));
}

it('удаляет отзыв и пишет AdminDeletedReview в журнал', function (): void {
    $review = restoredReview();
    $reviews = fakeReviewsRepository([$review]);
    $logs = fakeActionLogRepository();

    (new DeleteReviewHandler($reviews, recordActionHandlerWith($logs)))->handle(
        new DeleteReviewCommand(reviewId: $review->id->value, reason: 'спам'),
    );

    expect($reviews->reviews)->toBeEmpty()
        ->and($logs->logs)->toHaveCount(1)
        ->and($logs->logs[0]->type)->toBe(ActionType::AdminDeletedReview)
        ->and($logs->logs[0]->placeId->value)->toBe($review->placeId->value)
        ->and($logs->logs[0]->metadata)->toBe([
            'review_id' => $review->id->value,
            'stars' => $review->stars->value,
            'reason' => 'спам',
        ]);
});

it('не кладёт пустую reason в metadata', function (): void {
    $review = restoredReview();
    $logs = fakeActionLogRepository();

    (new DeleteReviewHandler(fakeReviewsRepository([$review]), recordActionHandlerWith($logs)))
        ->handle(new DeleteReviewCommand(reviewId: $review->id->value));

    expect($logs->logs[0]->metadata)->toBe([
        'review_id' => $review->id->value,
        'stars' => $review->stars->value,
    ]);
});

it('бросает ReviewNotFound для несуществующего отзыва', function (): void {
    (new DeleteReviewHandler(fakeReviewsRepository(), recordActionHandlerWith(fakeActionLogRepository())))
        ->handle(new DeleteReviewCommand(reviewId: '00000000-0000-0000-0000-000000000000'));
})->throws(ReviewNotFound::class);
