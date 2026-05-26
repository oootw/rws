<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Application\Jobs\FailedJobsActions;
use App\Application\Jobs\FailedJobsReader;
use App\Application\Jobs\FailedJobView;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Кастомная страница админ-панели: просмотр и управление failed_jobs.
 *
 * Использует не Resource, а Page — потому что нет Eloquent-модели
 * для `failed_jobs` (это таблица Laravel'овского queue-инфраструктуры,
 * а не доменная сущность).
 *
 * Чтение и операции — через порты `FailedJobsReader` / `FailedJobsActions`,
 * сама страница тонкая (адаптерный слой).
 */
final class FailedJobsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Упавшие задачи';

    protected static ?string $title = 'Упавшие задачи';

    protected static string|\UnitEnum|null $navigationGroup = 'Операционная';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'failed-jobs';

    protected string $view = 'filament.pages.failed-jobs';

    /** @var list<FailedJobView> */
    public array $jobs = [];

    public int $total = 0;

    public function mount(): void
    {
        $this->refreshList();
    }

    public function refreshList(): void
    {
        $reader = app(FailedJobsReader::class);

        $this->jobs = array_map(
            static fn (FailedJobView $job): array => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'jobClass' => $job->jobClass,
                'exceptionFirstLine' => $job->exceptionFirstLine,
                'failedAt' => $job->failedAt->format('d.m.Y H:i:s'),
            ],
            $reader->all(),
        );
        $this->total = $reader->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refreshList()),

            Action::make('prune')
                ->label('Очистить старые')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Удалить старые упавшие задачи')
                ->schema([
                    Select::make('older_than_days')
                        ->label('Старше скольких дней удалить')
                        ->options([
                            7 => '7 дней',
                            14 => '14 дней',
                            30 => '30 дней',
                            0 => 'Все (без ограничения)',
                        ])
                        ->default(7)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $count = app(FailedJobsActions::class)->prune((int) $data['older_than_days']);

                    Notification::make()
                        ->title("Удалено: {$count}")
                        ->success()
                        ->send();

                    $this->refreshList();
                }),
        ];
    }

    public function retry(string $uuid): void
    {
        $ok = app(FailedJobsActions::class)->retry($uuid);

        Notification::make()
            ->title($ok ? 'Задача поставлена в очередь' : 'Не удалось перезапустить')
            ->{$ok ? 'success' : 'danger'}()
            ->send();

        $this->refreshList();
    }

    public function deleteJob(string $uuid): void
    {
        $ok = app(FailedJobsActions::class)->delete($uuid);

        Notification::make()
            ->title($ok ? 'Запись удалена' : 'Запись не найдена')
            ->{$ok ? 'success' : 'warning'}()
            ->send();

        $this->refreshList();
    }
}
