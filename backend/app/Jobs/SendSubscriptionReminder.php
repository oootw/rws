<?php

namespace App\Jobs;

use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdQuery;
use App\Application\Notifications\RemindAboutSubscriptionExpiry\RemindAboutSubscriptionExpiryCommand;
use App\Application\Notifications\RemindAboutSubscriptionExpiry\RemindAboutSubscriptionExpiryHandler;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Каждое утро ищет владельцев, у кого подписка истекает ровно через
 * `reminder_days_before` дней, и шлёт им напоминание.
 */
final class SendSubscriptionReminder implements ShouldQueue
{
    use Queueable;

    public function handle(
        GetOwnerByIdHandler $getOwner,
        RemindAboutSubscriptionExpiryHandler $remind,
    ): void {
        $daysBefore = (int) config('guardreviews.subscription.reminder_days_before', 3);
        $targetDate = Carbon::now('Europe/Moscow')->addDays($daysBefore);

        User::query()
            ->whereNotNull('subscription_ends_at')
            ->whereBetween('subscription_ends_at', [
                $targetDate->copy()->startOfDay()->utc(),
                $targetDate->copy()->endOfDay()->utc(),
            ])
            ->chunkById(50, function ($users) use ($getOwner, $remind, $daysBefore): void {
                foreach ($users as $user) {
                    $owner = $getOwner->handle(new GetOwnerByIdQuery(ownerId: (string) $user->id));
                    $expiresAt = $owner?->subscription()->endsAt;

                    if ($owner === null || $expiresAt === null) {
                        continue;
                    }

                    $remind->handle(new RemindAboutSubscriptionExpiryCommand(
                        contact: $owner->asNotificationContact(),
                        expiresAt: DateTimeImmutable::createFromInterface($expiresAt),
                        daysBefore: $daysBefore,
                    ));
                }
            });
    }
}
