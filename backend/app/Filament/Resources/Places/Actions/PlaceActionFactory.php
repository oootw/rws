<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places\Actions;

use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationCommand;
use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationHandler;
use App\Domain\Places\Place as DomainPlace;
use App\Infrastructure\Persistence\Eloquent\Places\PlaceMapper;
use App\Models\Place;
use App\Models\User;
use App\Services\QrCodeService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Фабрика custom-action'ов для PlaceResource.
 *
 * Аналог OwnerActionFactory: тонкий адаптер между Filament UI и
 * Application use cases / прикладными сервисами (QrCodeService).
 * Бизнес-логики не содержит.
 */
final class PlaceActionFactory
{
    public static function toggleActivation(): Action
    {
        return Action::make('toggle_activation')
            ->label(fn (Place $record): string => $record->is_active ? 'Выключить' : 'Включить')
            ->icon(fn (Place $record): string => $record->is_active
                ? 'heroicon-o-pause-circle'
                : 'heroicon-o-play-circle')
            ->color(fn (Place $record): string => $record->is_active ? 'warning' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn (Place $record): string => $record->is_active
                ? 'Выключить точку?'
                : 'Включить точку?')
            ->modalDescription('Выключенная точка перестанет отдаваться публичным сценариям (QR/редирект/отзывы).')
            ->action(function (Place $record): void {
                $wasActive = (bool) $record->is_active;

                app(ChangePlaceActivationHandler::class)->handle(new ChangePlaceActivationCommand(
                    placeId: (string) $record->id,
                    active: ! $wasActive,
                ));

                Notification::make()
                    ->title($wasActive ? 'Точка выключена' : 'Точка включена')
                    ->success()
                    ->send();
            });
    }

    public static function previewQr(): Action
    {
        return Action::make('preview_qr')
            ->label('QR-код')
            ->icon('heroicon-o-qr-code')
            ->modalHeading(fn (Place $record): string => "QR для «{$record->title}»")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Закрыть')
            ->modalContent(fn (Place $record) => view('filament.places.qr-preview', [
                'url' => self::scanUrl($record),
                'png' => base64_encode(self::pngBytes($record)),
            ]));
    }

    public static function downloadQr(): Action
    {
        return Action::make('download_qr')
            ->label('Скачать QR')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn (Place $record): StreamedResponse => ResponseFacade::streamDownload(
                callback: function () use ($record): void {
                    echo self::pngBytes($record);
                },
                name: 'qr-'.$record->id.'.png',
                headers: ['Content-Type' => 'image/png'],
            ));
    }

    public static function bulkActivate(): BulkAction
    {
        return self::bulkChangeActivation(
            name: 'bulk_activate',
            label: 'Включить выбранные',
            icon: 'heroicon-o-play-circle',
            color: 'success',
            active: true,
            notificationTitle: 'Точки включены',
        );
    }

    public static function bulkDeactivate(): BulkAction
    {
        return self::bulkChangeActivation(
            name: 'bulk_deactivate',
            label: 'Выключить выбранные',
            icon: 'heroicon-o-pause-circle',
            color: 'warning',
            active: false,
            notificationTitle: 'Точки выключены',
        );
    }

    private static function bulkChangeActivation(
        string $name,
        string $label,
        string $icon,
        string $color,
        bool $active,
        string $notificationTitle,
    ): BulkAction {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->action(function (Collection $records) use ($active, $notificationTitle): void {
                $handler = app(ChangePlaceActivationHandler::class);

                foreach ($records as $record) {
                    $handler->handle(new ChangePlaceActivationCommand(
                        placeId: (string) $record->id,
                        active: $active,
                    ));
                }

                Notification::make()
                    ->title($notificationTitle)
                    ->body('Обработано: '.$records->count())
                    ->success()
                    ->send();
            });
    }

    private static function scanUrl(Place $record): string
    {
        $owner = User::query()->findOrFail($record->user_id);
        $base = "https://{$owner->subdomain_slug}.".config('guardreviews.domain', 'otziv.space');

        return app(QrCodeService::class)->placeScanUrl($base, self::toDomain($record));
    }

    private static function pngBytes(Place $record): string
    {
        return app(QrCodeService::class)->pngBytes(self::scanUrl($record));
    }

    private static function toDomain(Place $record): DomainPlace
    {
        return app(PlaceMapper::class)->toDomain($record);
    }
}
