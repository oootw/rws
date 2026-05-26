<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Pages;

use App\Application\Reviews\DeleteReview\DeleteReviewCommand;
use App\Application\Reviews\DeleteReview\DeleteReviewHandler;
use App\Filament\Resources\Reviews\Actions\ReviewActionFactory;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

final class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ReviewActionFactory::changeStatus(),
            ReviewActionFactory::resendAlert(),
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
}
