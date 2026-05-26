<?php

declare(strict_types=1);

namespace App\Interface\Filament\Audit;

use App\Application\Admin\RecordAdminAction\RecordAdminActionCommand;
use App\Application\Admin\RecordAdminAction\RecordAdminActionHandler;
use App\Interface\Filament\Auth\AdminUser;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Слушатель Filament-события ActionCalled.
 *
 * Filament диспатчит событие как `Event::dispatch(ActionCalled::class, $action)` —
 * передаёт строку-имя класса и объект Action как payload (а не ActionCalled-объект),
 * поэтому Laravel передаёт listener'у напрямую Action. Метод handle() принимает
 * Action соответственно.
 *
 * Любой бросок гасим — отказ записи лога не должен валить admin-action.
 */
final class LogAdminActionListener
{
    public function handle(Action $action): void
    {
        try {
            $admin = auth('admin')->user();

            if (! $admin instanceof AdminUser) {
                return;
            }

            $request = request();

            app(RecordAdminActionHandler::class)->handle(new RecordAdminActionCommand(
                adminEmail: $admin->getEmail(),
                action: (string) ($action->getName() ?? 'unknown'),
                resource: self::resolveResource($action),
                recordId: self::resolveRecordId($action),
                payload: self::resolveArguments($action),
                ip: $request->ip(),
                userAgent: substr((string) $request->userAgent(), 0, 1024),
            ));
        } catch (Throwable) {
            // Аудит не должен валить admin-action.
        }
    }

    private static function resolveResource(Action $action): ?string
    {
        $livewire = $action->getLivewire();

        return $livewire === null ? null : $livewire::class;
    }

    private static function resolveRecordId(Action $action): ?string
    {
        if (! method_exists($action, 'getRecord')) {
            return null;
        }

        $record = $action->getRecord();

        if ($record instanceof Model) {
            return (string) $record->getKey();
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function resolveArguments(Action $action): ?array
    {
        $arguments = $action->getArguments();

        return $arguments === [] ? null : $arguments;
    }
}
