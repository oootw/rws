<?php

declare(strict_types=1);

namespace App\Application\Notifications\SendWeeklyDigest;

use App\Application\Notifications\OwnerNotification;
use App\Application\Notifications\OwnerNotifier;
use App\Domain\Analytics\WeeklySummary;

final readonly class SendWeeklyDigestHandler
{
    public function __construct(
        private OwnerNotifier $notifier,
    ) {}

    public function handle(SendWeeklyDigestCommand $command): void
    {
        $text = $this->formatDigest($command->placeTitle, $command->summary);

        $this->notifier->notify(new OwnerNotification(
            contact: $command->contact,
            text: $text,
            emailSubject: "Отчёт за неделю — {$command->placeTitle}",
        ));
    }

    private function formatDigest(string $placeTitle, WeeklySummary $summary): string
    {
        return implode("\n", [
            "📊 Отчёт за 7 дней — {$placeTitle}",
            "Сканирований: {$summary->scanned}",
            "Ушли на площадки: {$summary->redirectedExternal}",
            "Перехвачено негативных: {$summary->leftNegative}",
            "Конверсия: {$summary->externalConversionPercent()}%",
        ]);
    }
}
