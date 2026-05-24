<?php

declare(strict_types=1);

namespace App\Application\Notifications\AlertAboutCriticalError;

use App\Application\Notifications\AdminNotifier;
use App\Application\Notifications\OwnerNotification;
use App\Application\Notifications\OwnerNotifier;
use App\Domain\Notifications\OwnerContact;

/**
 * Use case: критическая ошибка на форме отзыва.
 * Уведомляются и владелец, и команда (founder) — параллельно, чтобы оба
 * могли среагировать.
 */
final readonly class AlertAboutCriticalErrorHandler
{
    private const SUBJECT = 'Критическая ошибка на scan-форме';

    public function __construct(
        private OwnerNotifier $owners,
        private AdminNotifier $admins,
    ) {}

    public function handle(AlertAboutCriticalErrorCommand $command): void
    {
        $body = $this->formatBody($command);

        $this->notifyOwnerByEmail($command, $body);
        $this->admins->alert(self::SUBJECT, $body);
    }

    private function notifyOwnerByEmail(AlertAboutCriticalErrorCommand $command, string $body): void
    {
        if ($command->ownerEmail === null) {
            return;
        }

        $this->owners->notify(new OwnerNotification(
            contact: new OwnerContact(telegramId: null, maxId: null, email: $command->ownerEmail),
            text: $body,
            emailSubject: self::SUBJECT,
        ));
    }

    private function formatBody(AlertAboutCriticalErrorCommand $command): string
    {
        return implode("\n", [
            "Контекст: {$command->context}",
            "Точка: {$command->placeTitle} ({$command->placeId})",
            "Владелец: {$command->ownerName} ({$command->ownerEmail})",
            "Поддомен: {$command->ownerSubdomain}",
        ]);
    }
}
