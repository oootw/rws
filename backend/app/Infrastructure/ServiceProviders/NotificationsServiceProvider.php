<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Application\Notifications\AdminNotifier;
use App\Application\Notifications\Logging\NotificationDeliveryLogger;
use App\Application\Notifications\OwnerNotifier;
use App\Infrastructure\Notifications\Channels\EmailNotificationChannel;
use App\Infrastructure\Notifications\Channels\MaxNotificationChannel;
use App\Infrastructure\Notifications\Channels\TelegramNotificationChannel;
use App\Infrastructure\Notifications\EmailAdminNotifier;
use App\Infrastructure\Notifications\Logging\EloquentNotificationDeliveryLogger;
use App\Infrastructure\Notifications\MultiChannelOwnerNotifier;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Композиция подсистемы уведомлений: какой канал в каком приоритете,
 * куда падает фолбэк. Менять политику доставки = править один файл.
 */
final class NotificationsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        NotificationDeliveryLogger::class => EloquentNotificationDeliveryLogger::class,
    ];

    public function register(): void
    {
        $this->app->singleton(AdminNotifier::class, EmailAdminNotifier::class);

        $this->app->singleton(OwnerNotifier::class, function (Container $app): OwnerNotifier {
            return new MultiChannelOwnerNotifier(
                instantChannels: [
                    $app->make(TelegramNotificationChannel::class),
                    $app->make(MaxNotificationChannel::class),
                ],
                fallbackChannels: [
                    $app->make(EmailNotificationChannel::class),
                ],
                logger: $app->make(LoggerInterface::class),
                deliveryLogger: $app->make(NotificationDeliveryLogger::class),
            );
        });
    }
}
