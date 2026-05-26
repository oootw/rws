<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActionLogs\Schemas;

use App\Domain\Analytics\ActionType;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ActionLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Запись')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Когда')
                        ->dateTime('d.m.Y H:i:s'),

                    TextEntry::make('action_type')
                        ->label('Тип')
                        ->badge()
                        ->formatStateUsing(fn ($state): string => ActionLogTypeLabels::for($state))
                        ->color(fn ($state): string => ActionLogTypeLabels::color($state)),

                    TextEntry::make('place.title')->label('Точка')->placeholder('—'),
                    TextEntry::make('place.user.name')->label('Владелец')->placeholder('—'),

                    TextEntry::make('id')->label('ID записи')->copyable(),
                    TextEntry::make('place_id')->label('ID точки')->copyable(),
                ]),

            Section::make('Metadata')
                ->collapsible()
                ->schema([
                    KeyValueEntry::make('metadata')
                        ->label('')
                        ->hiddenLabel()
                        ->placeholder('—'),
                ]),
        ]);
    }
}
