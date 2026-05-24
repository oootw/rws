<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Callbacks;

use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusCommand;
use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusHandler;
use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusResult;
use App\Enums\ReviewStatus;
use App\Interface\TelegramBot\Support\TelegramOwnerResolver;
use SergiX44\Nutgram\Nutgram;

final class ReviewCallbackHandler
{
    public function __construct(
        private readonly TelegramOwnerResolver $ownerResolver,
        private readonly ChangeReviewStatusHandler $changeStatus,
    ) {}

    public function __invoke(Nutgram $bot, string $reviewId, string $statusValue): void
    {
        $owner = $this->ownerResolver->resolve($bot);
        $status = ReviewStatus::tryFrom($statusValue);

        if ($owner === null) {
            $bot->answerCallbackQuery('Сначала пройдите регистрацию: /start', show_alert: true);

            return;
        }

        if ($status === null) {
            $bot->answerCallbackQuery('Некорректный статус.', show_alert: true);

            return;
        }

        $result = $this->changeStatus->handle(new ChangeReviewStatusCommand(
            reviewId: $reviewId,
            ownerId: $owner->id->value,
            newStatus: $status,
        ));

        match ($result) {
            ChangeReviewStatusResult::Updated => $bot->answerCallbackQuery('Статус обновлён.'),
            ChangeReviewStatusResult::ReviewNotFound => $bot->answerCallbackQuery('Отзыв не найден.', show_alert: true),
            ChangeReviewStatusResult::NotOwnedByCaller => $bot->answerCallbackQuery('Нет доступа к этому отзыву.', show_alert: true),
        };
    }
}
