<?php

use App\Application\Analytics\GetWeeklySummary\GetWeeklySummaryHandler;
use App\Application\Analytics\GetWeeklySummary\GetWeeklySummaryQuery;
use App\Application\Analytics\RecordAction\RecordActionCommand;
use App\Application\Analytics\RecordAction\RecordActionHandler;
use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountHandler;
use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountQuery;
use App\Application\Notifications\OwnerNotification;
use App\Application\Notifications\OwnerNotifier;
use App\Domain\Analytics\ActionType;
use App\Domain\Notifications\OwnerContact;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;
use App\Models\ActionLog;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs scan actions with metadata', function (): void {
    $place = Place::factory()->create();

    app(RecordActionHandler::class)->handle(new RecordActionCommand(
        placeId: (string) $place->id,
        type: ActionType::RedirectedExternal,
        metadata: ['platform' => '2gis'],
    ));

    $log = ActionLog::query()->where('place_id', $place->id)->firstOrFail();

    expect($log->action_type)->toBe(ActionType::RedirectedExternal)
        ->and($log->metadata)->toBe(['platform' => '2gis'])
        ->and($log->created_at)->not->toBeNull();
});

it('summarizes weekly action log counts per place', function (): void {
    $place = Place::factory()->create();
    $record = app(RecordActionHandler::class);

    $record->handle(new RecordActionCommand((string) $place->id, ActionType::Scanned));
    $record->handle(new RecordActionCommand((string) $place->id, ActionType::Scanned));
    $record->handle(new RecordActionCommand((string) $place->id, ActionType::RedirectedExternal));
    $record->handle(new RecordActionCommand((string) $place->id, ActionType::LeftNegative));

    $summary = app(GetWeeklySummaryHandler::class)
        ->handle(new GetWeeklySummaryQuery(placeId: (string) $place->id));

    expect($summary->scanned)->toBe(2)
        ->and($summary->redirectedExternal)->toBe(1)
        ->and($summary->leftNegative)->toBe(1);
});

it('filters empty platform urls in domain aggregate', function (): void {
    $model = Place::factory()->create([
        'platforms' => [
            ['type' => '2gis', 'url' => 'https://2gis.ru/example', 'label' => '2GIS'],
            ['type' => 'yandex', 'url' => '', 'label' => 'Яндекс'],
        ],
    ]);

    $place = app(PlaceRepository::class)
        ->findById(new PlaceId((string) $model->id));

    expect($place)->not->toBeNull()
        ->and($place->platforms())->toHaveCount(1)
        ->and($place->hasConfiguredPlatforms())->toBeTrue();
});

it('calculates subscription amount based on place count', function (): void {
    $user = User::factory()->create();
    Place::factory()->count(3)->for($user)->create();

    $amount = app(CalculateSubscriptionAmountHandler::class)
        ->handle(new CalculateSubscriptionAmountQuery(ownerId: (string) $user->id));

    expect($amount)->toBe(99000 + (2 * 29000));
});

it('falls back to e-mail when no instant channel is configured', function (): void {
    config(['nutgram.token' => null]);

    $sent = [];

    $this->app->bind(OwnerNotifier::class, function () use (&$sent): OwnerNotifier {
        return new class($sent) implements OwnerNotifier
        {
            /** @param list<OwnerNotification> $sent */
            public function __construct(public array &$sent) {}

            public function notify(OwnerNotification $notification): void
            {
                $this->sent[] = $notification;
            }
        };
    });

    $notifier = app(OwnerNotifier::class);

    $notifier->notify(new OwnerNotification(
        contact: new OwnerContact(telegramId: null, maxId: null, email: 'owner@example.com'),
        text: 'тело',
        emailSubject: 'тема',
    ));

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->contact->email)->toBe('owner@example.com');
});
