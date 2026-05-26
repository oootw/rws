<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places\Schemas;

use App\Domain\Places\PlatformType;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Форма create/edit точки.
 *
 * Repeater платформ — это «сырые» строки (type/url/label), которые
 * use case (RegisterPlace / UpdatePlace) превратит в PlatformLink через
 * PlatformsBuilder. Eloquent::save() в Page-классах перехвачен.
 */
final class PlaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),

                    Select::make('user_id')
                        ->label('Владелец')
                        ->options(fn () => User::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabledOn('edit')
                        ->helperText('Владелец задаётся при создании и не меняется.'),

                    TextInput::make('background_image_url')
                        ->label('URL фоновой картинки')
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull()
                        ->nullable(),
                ]),

            Section::make('Площадки')
                ->description('Куда отправлять посетителя, оставившего положительный отзыв.')
                ->schema([
                    Repeater::make('platforms')
                        ->label('')
                        ->hiddenLabel()
                        ->addActionLabel('Добавить площадку')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->defaultItems(0)
                        ->schema([
                            Select::make('type')
                                ->label('Тип')
                                ->options(self::platformTypeOptions())
                                ->required()
                                ->native(false),

                            TextInput::make('label')
                                ->label('Подпись кнопки')
                                ->required()
                                ->maxLength(64),

                            TextInput::make('url')
                                ->label('Ссылка')
                                ->url()
                                ->required()
                                ->maxLength(2048),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    /**
     * Готовит массив platforms для Filament-репитера из persistence-формата.
     * В БД хранится list<['type','url','label']> в JSON-колонке — это и есть
     * формат, который ожидает форма.
     *
     * @return array<string, mixed>
     */
    public static function modelToFormData(Model $record): array
    {
        return [
            'title' => $record->title,
            'user_id' => $record->user_id,
            'background_image_url' => $record->background_image_url,
            'platforms' => $record->platforms ?? [],
        ];
    }

    /**
     * Filament Repeater отдаёт array<string, array{...}> (с UUID-ключами).
     * Use case ожидает list — выравниваем через array_values и приводим
     * к ожидаемой форме без необязательных ключей.
     *
     * @param  array<int|string, array{type?: string, url?: string, label?: string}>  $raw
     * @return list<array{type: string, url: string, label: string}>
     */
    public static function normalizeRepeaterPlatforms(array $raw): array
    {
        $result = [];

        foreach (array_values($raw) as $entry) {
            $result[] = [
                'type' => (string) ($entry['type'] ?? ''),
                'url' => (string) ($entry['url'] ?? ''),
                'label' => (string) ($entry['label'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private static function platformTypeOptions(): array
    {
        return [
            PlatformType::TwoGis->value => '2GIS',
            PlatformType::Yandex->value => 'Яндекс Карты',
            PlatformType::Custom->value => 'Своя площадка',
        ];
    }
}
