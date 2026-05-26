<?php

declare(strict_types=1);

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\OwnerNotification;
use App\Domain\Notifications\OwnerContact;
use App\Application\Notifications\Logging\NotificationDeliveryLogger;
use App\Application\Notifications\Logging\NotificationDeliveryStatus;
use App\Infrastructure\Notifications\MultiChannelOwnerNotifier;
use App\Infrastructure\Notifications\NotificationDeliveryFailed;
use Psr\Log\NullLogger;

function recordingChannel(bool $supports, ?Throwable $throws = null): NotificationChannel
{
    return new class($supports, $throws) implements NotificationChannel
    {
        public int $delivered = 0;

        public function __construct(private bool $supports, private ?Throwable $throws) {}

        public function supports(OwnerNotification $notification): bool
        {
            return $this->supports;
        }

        public function deliver(OwnerNotification $notification): void
        {
            if ($this->throws !== null) {
                throw $this->throws;
            }

            $this->delivered++;
        }
    };
}

function notificationToOwner(): OwnerNotification
{
    return new OwnerNotification(
        contact: new OwnerContact(telegramId: '1', maxId: null, email: 'owner@example.com'),
        text: 'тело',
        emailSubject: 'тема',
    );
}

function nullDeliveryLogger(): NotificationDeliveryLogger
{
    return new class implements NotificationDeliveryLogger
    {
        public function log(?string $ownerId, string $channel, string $kind, NotificationDeliveryStatus $status, ?string $error = null): void {}
    };
}

function makeNotifier(array $instant, array $fallback): MultiChannelOwnerNotifier
{
    return new MultiChannelOwnerNotifier(
        instantChannels: $instant,
        fallbackChannels: $fallback,
        logger: new NullLogger,
        deliveryLogger: nullDeliveryLogger(),
    );
}

it('доставляет через первый мгновенный канал и не вызывает резервный', function (): void {
    $telegram = recordingChannel(supports: true);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram], [$email])->notify(notificationToOwner());

    expect($telegram->delivered)->toBe(1)
        ->and($email->delivered)->toBe(0);
});

it('переключается на резервный канал, если ни один мгновенный не поддерживает', function (): void {
    $telegram = recordingChannel(supports: false);
    $max = recordingChannel(supports: false);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram, $max], [$email])->notify(notificationToOwner());

    expect($telegram->delivered)->toBe(0)
        ->and($max->delivered)->toBe(0)
        ->and($email->delivered)->toBe(1);
});

it('вызывает все поддерживающие мгновенные каналы параллельно', function (): void {
    $telegram = recordingChannel(supports: true);
    $max = recordingChannel(supports: true);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram, $max], [$email])->notify(notificationToOwner());

    expect($telegram->delivered)->toBe(1)
        ->and($max->delivered)->toBe(1)
        ->and($email->delivered)->toBe(0);
});

it('переключается на резервный канал, если все мгновенные каналы упали', function (): void {
    $telegram = recordingChannel(supports: true, throws: new RuntimeException('tg down'));
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram], [$email])->notify(notificationToOwner());

    expect($email->delivered)->toBe(1);
});

it('ошибка одного мгновенного канала не блокирует другой', function (): void {
    $telegram = recordingChannel(supports: true, throws: new RuntimeException('tg down'));
    $max = recordingChannel(supports: true);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram, $max], [$email])->notify(notificationToOwner());

    expect($max->delivered)->toBe(1)
        ->and($email->delivered)->toBe(0); // max справился, на email не падаем
});

it('бросает «не удалось доставить уведомление», если ни один канал не сработал', function (): void {
    $telegram = recordingChannel(supports: true, throws: new RuntimeException('tg down'));
    $email = recordingChannel(supports: true, throws: new RuntimeException('smtp down'));

    makeNotifier([$telegram], [$email])->notify(notificationToOwner());
})->throws(NotificationDeliveryFailed::class);
