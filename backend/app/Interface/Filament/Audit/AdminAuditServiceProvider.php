<?php

declare(strict_types=1);

namespace App\Interface\Filament\Audit;

use Filament\Actions\Events\ActionCalled;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

/**
 * Подписывает LogAdminActionListener на событие ActionCalled из Filament.
 * Слушатель пишет аудит-запись через RecordAdminActionHandler.
 */
final class AdminAuditServiceProvider extends ServiceProvider
{
    public function boot(Dispatcher $events): void
    {
        $events->listen(ActionCalled::class, [LogAdminActionListener::class, 'handle']);
    }
}
