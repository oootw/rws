<?php

declare(strict_types=1);

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\OwnerNotification;
use App\Domain\Notifications\OwnerContact;
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

function makeNotifier(array $instant, array $fallback): MultiChannelOwnerNotifier
{
    return new MultiChannelOwnerNotifier(
        instantChannels: $instant,
        fallbackChannels: $fallback,
        logger: new NullLogger,
    );
}

it('доставляет через первый поддерживающий instant-канал и не зовёт fallback', function (): void {
    $telegram = recordingChannel(supports: true);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram], [$email])->notify(notificationToOwner());

    expect($telegram->delivered)->toBe(1)
        ->and($email->delivered)->toBe(0);
});

it('падает на fallback, если ни один instant-канал не поддерживает', function (): void {
    $telegram = recordingChannel(supports: false);
    $max = recordingChannel(supports: false);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram, $max], [$email])->notify(notificationToOwner());

    expect($telegram->delivered)->toBe(0)
        ->and($max->delivered)->toBe(0)
        ->and($email->delivered)->toBe(1);
});

it('зовёт все поддерживающие instant-каналы параллельно', function (): void {
    $telegram = recordingChannel(supports: true);
    $max = recordingChannel(supports: true);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram, $max], [$email])->notify(notificationToOwner());

    expect($telegram->delivered)->toBe(1)
        ->and($max->delivered)->toBe(1)
        ->and($email->delivered)->toBe(0);
});

it('падает на fallback, если все instant-каналы кинули исключение', function (): void {
    $telegram = recordingChannel(supports: true, throws: new RuntimeException('tg down'));
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram], [$email])->notify(notificationToOwner());

    expect($email->delivered)->toBe(1);
});

it('кинутое одним instant-каналом исключение не блокирует другой instant', function (): void {
    $telegram = recordingChannel(supports: true, throws: new RuntimeException('tg down'));
    $max = recordingChannel(supports: true);
    $email = recordingChannel(supports: true);

    makeNotifier([$telegram, $max], [$email])->notify(notificationToOwner());

    expect($max->delivered)->toBe(1)
        ->and($email->delivered)->toBe(0); // max справился, на email не падаем
});

it('бросает NotificationDeliveryFailed, если ни один канал не сработал', function (): void {
    $telegram = recordingChannel(supports: true, throws: new RuntimeException('tg down'));
    $email = recordingChannel(supports: true, throws: new RuntimeException('smtp down'));

    makeNotifier([$telegram], [$email])->notify(notificationToOwner());
})->throws(NotificationDeliveryFailed::class);
