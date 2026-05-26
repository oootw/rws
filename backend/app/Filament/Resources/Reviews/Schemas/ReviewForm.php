<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Форма отзыва — только статус. Остальное (stars/text/contact/place)
 * редактировать нельзя: это правдивые данные посетителя, любые правки
 * исказили бы аналитику.
 */
final class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Статус модерации')
                ->schema([
                    Select::make('status')
                        ->label('Статус')
                        ->options(self::statusOptions())
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            ReviewStatus::New->value => 'Новый',
            ReviewStatus::InProgress->value => 'В работе',
            ReviewStatus::Resolved->value => 'Решён',
            ReviewStatus::Archived->value => 'Архив',
        ];
    }
}
