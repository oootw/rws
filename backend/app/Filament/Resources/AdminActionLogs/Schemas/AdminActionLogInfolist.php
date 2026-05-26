<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminActionLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AdminActionLogInfolist
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

                    TextEntry::make('admin_email')->label('Админ'),

                    TextEntry::make('action')->label('Действие')->badge(),

                    TextEntry::make('resource')
                        ->label('Ресурс')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('record_id')
                        ->label('ID записи')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('ip')->label('IP')->placeholder('—'),

                    TextEntry::make('user_agent')
                        ->label('User-Agent')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Payload')
                ->collapsible()
                ->schema([
                    KeyValueEntry::make('payload')
                        ->label('')
                        ->hiddenLabel()
                        ->placeholder('—'),
                ]),
        ]);
    }
}
