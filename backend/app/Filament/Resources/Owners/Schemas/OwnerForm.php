<?php

declare(strict_types=1);

namespace App\Filament\Resources\Owners\Schemas;

use App\Models\Tariff;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Форма create/edit владельца.
 *
 * Поля биндятся напрямую на колонки таблицы users — но сохранение
 * перехвачено в Page-классах (CreateOwner, EditOwner) и идёт через
 * соответствующие Application use cases. Eloquent::save() не вызывается.
 */
final class OwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Профиль')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Имя владельца')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    TextInput::make('subdomain_slug')
                        ->label('Поддомен')
                        ->required()
                        ->maxLength(32)
                        ->helperText('Только латиница, цифры и дефис; 3–32 символа.')
                        ->rule('regex:/^[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])?$/')
                        ->unique(
                            table: 'users',
                            column: 'subdomain_slug',
                            ignoreRecord: true,
                        ),

                    Select::make('tariff_id')
                        ->label('Тариф')
                        ->options(fn () => Tariff::query()->pluck('title', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),

            Section::make('Контакты')
                ->columns(2)
                ->schema([
                    TextInput::make('telegram_id')
                        ->label('Telegram ID')
                        ->maxLength(64)
                        ->nullable()
                        ->helperText('Числовой ID. Узнаётся через @userinfobot.'),

                    TextInput::make('max_id')
                        ->label('MAX ID')
                        ->maxLength(64)
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Привязывается через бот командой /link, недоступно для редактирования.'),
                ]),
        ]);
    }

    /**
     * Хелпер для предзаполнения формы при редактировании — Filament сам
     * мапит атрибуты модели на поля по name, но мы явно используем колонки
     * persistence-таблицы.
     *
     * @return array<string, mixed>
     */
    public static function modelToFormData(Model $record): array
    {
        return [
            'name' => $record->name,
            'email' => $record->email,
            'subdomain_slug' => $record->subdomain_slug,
            'telegram_id' => $record->telegram_id,
            'max_id' => $record->max_id,
            'tariff_id' => $record->tariff_id,
        ];
    }
}
