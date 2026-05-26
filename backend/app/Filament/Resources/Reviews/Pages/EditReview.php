<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Pages;

use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusCommand;
use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusHandler;
use App\Application\Reviews\DeleteReview\DeleteReviewCommand;
use App\Application\Reviews\DeleteReview\DeleteReviewHandler;
use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Редактирование отзыва = только смена статуса. Сохранение
 * проксируется в AdminChangeReviewStatusHandler. Eloquent::save()
 * формы не запускается.
 */
final class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить отзыв?')
                ->schema([
                    Textarea::make('reason')
                        ->label('Причина (необязательно)')
                        ->maxLength(500),
                ])
                ->action(function (Model $record, array $data): void {
                    app(DeleteReviewHandler::class)->handle(new DeleteReviewCommand(
                        reviewId: (string) $record->id,
                        reason: ! empty($data['reason']) ? (string) $data['reason'] : null,
                    ));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(AdminChangeReviewStatusHandler::class)->handle(new AdminChangeReviewStatusCommand(
            reviewId: (string) $record->id,
            newStatus: ReviewStatus::from((string) $data['status']),
        ));

        return $record->refresh();
    }
}
