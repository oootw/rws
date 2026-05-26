<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Actions;

use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusCommand;
use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusHandler;
use App\Application\Reviews\ResendNegativeReviewAlert\ResendNegativeReviewAlertCommand;
use App\Application\Reviews\ResendNegativeReviewAlert\ResendNegativeReviewAlertHandler;
use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * Фабрика custom-action'ов для ReviewResource.
 *
 * Тонкий адаптер между Filament UI и Application use cases;
 * бизнес-логики нет.
 */
final class ReviewActionFactory
{
    public static function changeStatus(): Action
    {
        return Action::make('change_status')
            ->label('Сменить статус')
            ->icon('heroicon-o-arrows-right-left')
            ->schema([
                Select::make('status')
                    ->label('Новый статус')
                    ->options(ReviewForm::statusOptions())
                    ->required()
                    ->native(false),
            ])
            ->action(function (Review $record, array $data): void {
                app(AdminChangeReviewStatusHandler::class)->handle(new AdminChangeReviewStatusCommand(
                    reviewId: (string) $record->id,
                    newStatus: ReviewStatus::from((string) $data['status']),
                ));

                Notification::make()
                    ->title('Статус обновлён')
                    ->success()
                    ->send();
            });
    }

    public static function resendAlert(): Action
    {
        return Action::make('resend_alert')
            ->label('Переотправить уведомление')
            ->icon('heroicon-o-bell-alert')
            ->requiresConfirmation()
            ->modalDescription('Уведомление снова уйдёт в очередь и доберётся до владельца по основным каналам.')
            ->action(function (Review $record): void {
                app(ResendNegativeReviewAlertHandler::class)->handle(
                    new ResendNegativeReviewAlertCommand(reviewId: (string) $record->id),
                );

                Notification::make()
                    ->title('Уведомление поставлено в очередь')
                    ->success()
                    ->send();
            });
    }
}
