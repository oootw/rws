<?php

declare(strict_types=1);

namespace App\Filament\Resources\Owners\Actions;

use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffCommand;
use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffHandler;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionCommand;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionHandler;
use App\Application\Iam\IssueOwnerImpersonationToken\IssueOwnerImpersonationTokenCommand;
use App\Application\Iam\IssueOwnerImpersonationToken\IssueOwnerImpersonationTokenHandler;
use App\Application\Iam\OverrideSubscription\OverrideSubscriptionCommand;
use App\Application\Iam\OverrideSubscription\OverrideSubscriptionHandler;
use App\Models\Tariff;
use App\Models\User;
use DateTimeImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * Фабрика custom-action'ов для OwnerResource.
 *
 * Каждый action:
 *  1) Получает Eloquent\User как $record (Filament сам инжектит).
 *  2) Конвертирует ввод в Command нужного use case'а.
 *  3) Дёргает соответствующий Handler через сервис-контейнер.
 *  4) Показывает Filament Notification c результатом.
 *
 * Никакой бизнес-логики тут нет — это адаптерный слой между Filament UI
 * и Application use cases. Аналог тонких HTTP-контроллеров.
 */
final class OwnerActionFactory
{
    public static function extendSubscription(): Action
    {
        return Action::make('extend_subscription')
            ->label('Продлить подписку')
            ->icon('heroicon-o-clock')
            ->color('success')
            ->schema([
                TextInput::make('days')
                    ->label('Дней')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(3650)
                    ->default((int) config('guardreviews.subscription.duration_days', 30))
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
                $owner = app(ExtendSubscriptionHandler::class)->handle(new ExtendSubscriptionCommand(
                    ownerId: (string) $record->id,
                    durationDays: (int) $data['days'],
                ));

                Notification::make()
                    ->title('Подписка продлена')
                    ->body('Новая дата: '.$owner->subscription()->endsAt?->format('d.m.Y H:i'))
                    ->success()
                    ->send();
            });
    }

    public static function overrideSubscription(): Action
    {
        return Action::make('override_subscription')
            ->label('Изменить дату подписки')
            ->icon('heroicon-o-calendar-days')
            ->color('warning')
            ->schema([
                DateTimePicker::make('ends_at')
                    ->label('Действует до')
                    ->seconds(false)
                    ->helperText('Оставьте пустым, чтобы сбросить подписку.')
                    ->nullable(),
            ])
            ->action(function (User $record, array $data): void {
                $endsAt = ! empty($data['ends_at'])
                    ? new DateTimeImmutable((string) $data['ends_at'])
                    : null;

                app(OverrideSubscriptionHandler::class)->handle(new OverrideSubscriptionCommand(
                    ownerId: (string) $record->id,
                    endsAt: $endsAt,
                ));

                Notification::make()
                    ->title('Подписка обновлена')
                    ->body($endsAt !== null
                        ? 'Действует до '.$endsAt->format('d.m.Y H:i')
                        : 'Подписка сброшена.')
                    ->success()
                    ->send();
            });
    }

    public static function changeTariff(): Action
    {
        return Action::make('change_tariff')
            ->label('Сменить тариф')
            ->icon('heroicon-o-arrows-right-left')
            ->schema([
                Select::make('tariff_id')
                    ->label('Тариф')
                    ->options(fn () => Tariff::query()->pluck('title', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ])
            ->action(function (User $record, array $data): void {
                app(ChangeOwnerTariffHandler::class)->handle(new ChangeOwnerTariffCommand(
                    ownerId: (string) $record->id,
                    tariffId: ! empty($data['tariff_id']) ? (string) $data['tariff_id'] : null,
                ));

                Notification::make()
                    ->title('Тариф обновлён')
                    ->success()
                    ->send();
            });
    }

    public static function impersonate(): Action
    {
        return Action::make('impersonate')
            ->label('Имперсонация')
            ->icon('heroicon-o-key')
            ->color('danger')
            ->modalHeading('Выпустить токен имперсонации')
            ->modalDescription(
                'Sanctum-токен с ability "impersonated" — для отладки сценариев '.
                'от имени владельца. Полное plain-text значение будет показано '.
                'один раз после выпуска, потом — только хеш в БД.'
            )
            ->modalSubmitActionLabel('Выпустить токен')
            ->schema([
                TextInput::make('ttl')
                    ->label('TTL, минут')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(60)
                    ->default(15)
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
                $result = app(IssueOwnerImpersonationTokenHandler::class)->handle(
                    new IssueOwnerImpersonationTokenCommand(
                        ownerId: (string) $record->id,
                        ttlMinutes: (int) ($data['ttl'] ?? 15),
                    ),
                );

                Notification::make()
                    ->title('Токен имперсонации выпущен')
                    ->body(view('filament.notifications.impersonation-token', [
                        'token' => $result->plainTextToken,
                        'expiresAt' => $result->expiresAt,
                    ])->render())
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
