<?php

namespace App\Jobs;

use App\Application\Analytics\GetWeeklySummary\GetWeeklySummaryHandler;
use App\Application\Analytics\GetWeeklySummary\GetWeeklySummaryQuery;
use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdQuery;
use App\Application\Notifications\SendWeeklyDigest\SendWeeklyDigestCommand;
use App\Application\Notifications\SendWeeklyDigest\SendWeeklyDigestHandler;
use App\Domain\Iam\Owner;
use App\Models\Place;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Раз в неделю по расписанию (см. routes/console.php).
 *
 * Для итерации по владельцам пользуемся прямым Eloquent-запросом — это
 * чисто инфраструктурное "найти тех, кому есть что слать", без бизнес-смысла.
 * Дальше уже работают use case'ы: получить Owner из Iam, посчитать саммари,
 * собрать и отправить уведомление.
 */
final class SendWeeklyDigest implements ShouldQueue
{
    use Queueable;

    public function handle(
        GetOwnerByIdHandler $getOwner,
        GetWeeklySummaryHandler $getSummary,
        SendWeeklyDigestHandler $sendDigest,
    ): void {
        User::query()
            ->whereHas('places')
            ->with('places')
            ->chunkById(50, function ($users) use ($getOwner, $getSummary, $sendDigest): void {
                foreach ($users as $user) {
                    $owner = $getOwner->handle(new GetOwnerByIdQuery(ownerId: (string) $user->id));

                    if ($owner === null) {
                        continue;
                    }

                    foreach ($user->places as $place) {
                        $this->sendForPlace($getSummary, $sendDigest, $owner, $place);
                    }
                }
            });
    }

    private function sendForPlace(
        GetWeeklySummaryHandler $getSummary,
        SendWeeklyDigestHandler $sendDigest,
        Owner $owner,
        Place $place,
    ): void {
        $summary = $getSummary->handle(new GetWeeklySummaryQuery(placeId: (string) $place->id));

        $sendDigest->handle(new SendWeeklyDigestCommand(
            contact: $owner->asNotificationContact(),
            placeTitle: (string) $place->title,
            summary: $summary,
        ));
    }
}
