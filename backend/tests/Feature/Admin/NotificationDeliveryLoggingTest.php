<?php

declare(strict_types=1);

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\Logging\NotificationDeliveryStatus;
use App\Application\Notifications\OwnerNotification;
use App\Domain\Notifications\OwnerContact;
use App\Infrastructure\Notifications\Logging\EloquentNotificationDeliveryLogger;
use App\Infrastructure\Notifications\MultiChannelOwnerNotifier;
use App\Infrastructure\Notifications\NotificationDeliveryFailed;
use App\Models\NotificationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\NullLogger;

uses(RefreshDatabase::class);

function makeStubChannel(bool $supports, ?Throwable $throws = null): NotificationChannel
{
    return new class($supports, $throws) implements NotificationChannel
    {
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
        }
    };
}

function notificationFor(string $kind = 'negative_review', ?string $ownerId = 'owner-uuid'): OwnerNotification
{
    return new OwnerNotification(
        contact: new OwnerContact(telegramId: 'tg', maxId: null, email: 'o@x.io', ownerId: $ownerId),
        text: 'hello',
        emailSubject: 'Hello',
        kind: $kind,
    );
}

it('логирует Delivered при успешной отправке через канал', function (): void {
    $logger = new EloquentNotificationDeliveryLogger(new NullLogger);
    $notifier = new MultiChannelOwnerNotifier(
        instantChannels: [makeStubChannel(supports: true)],
        fallbackChannels: [],
        logger: new NullLogger,
        deliveryLogger: $logger,
    );

    $notifier->notify(notificationFor());

    $row = NotificationDelivery::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->status)->toBe(NotificationDeliveryStatus::Delivered)
        ->and($row->kind)->toBe('negative_review')
        ->and($row->owner_id)->toBe('owner-uuid')
        ->and($row->error)->toBeNull();
});

it('логирует Skipped, если канал не supports', function (): void {
    $logger = new EloquentNotificationDeliveryLogger(new NullLogger);
    $notifier = new MultiChannelOwnerNotifier(
        instantChannels: [makeStubChannel(supports: false)],
        fallbackChannels: [makeStubChannel(supports: true)], // fallback подхватит
        logger: new NullLogger,
        deliveryLogger: $logger,
    );

    $notifier->notify(notificationFor());

    $statuses = NotificationDelivery::query()->orderBy('created_at')->pluck('status')->all();
    expect($statuses)->toContain(NotificationDeliveryStatus::Skipped)
        ->and($statuses)->toContain(NotificationDeliveryStatus::Delivered);
});

it('логирует Failed и пробует следующие каналы при исключении', function (): void {
    $logger = new EloquentNotificationDeliveryLogger(new NullLogger);
    $notifier = new MultiChannelOwnerNotifier(
        instantChannels: [
            makeStubChannel(supports: true, throws: new RuntimeException('telegram down')),
        ],
        fallbackChannels: [makeStubChannel(supports: true)],
        logger: new NullLogger,
        deliveryLogger: $logger,
    );

    $notifier->notify(notificationFor());

    $failed = NotificationDelivery::query()->where('status', NotificationDeliveryStatus::Failed)->first();
    expect($failed)->not->toBeNull()
        ->and($failed->error)->toBe('telegram down');

    $delivered = NotificationDelivery::query()->where('status', NotificationDeliveryStatus::Delivered)->first();
    expect($delivered)->not->toBeNull();
});

it('пробрасывает NotificationDeliveryFailed, если ни один канал не сработал, но всё равно пишет лог', function (): void {
    $logger = new EloquentNotificationDeliveryLogger(new NullLogger);
    $notifier = new MultiChannelOwnerNotifier(
        instantChannels: [makeStubChannel(supports: true, throws: new RuntimeException('boom'))],
        fallbackChannels: [makeStubChannel(supports: true, throws: new RuntimeException('boom2'))],
        logger: new NullLogger,
        deliveryLogger: $logger,
    );

    expect(fn () => $notifier->notify(notificationFor()))
        ->toThrow(NotificationDeliveryFailed::class);

    expect(NotificationDelivery::query()->where('status', NotificationDeliveryStatus::Failed)->count())
        ->toBe(2);
});
