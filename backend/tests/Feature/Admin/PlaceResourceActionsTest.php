<?php

declare(strict_types=1);

use App\Filament\Resources\Places\Pages\CreatePlace;
use App\Filament\Resources\Places\Pages\ListPlaces;
use App\Filament\Resources\Places\Pages\ViewPlace;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\Place;
use App\Models\User;
use App\Services\QrCodeService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
        'guardreviews.domain' => 'otziv.space',
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    Filament::setCurrentPanel('admin');
    Filament::setServingStatus(true);
});

it('CreatePlace создаёт точку через RegisterPlaceHandler', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe']);

    Livewire::test(CreatePlace::class)
        ->fillForm([
            'title' => 'Новая точка',
            'user_id' => $owner->id,
            'background_image_url' => null,
            'platforms' => [
                [
                    'type' => 'yandex',
                    'label' => 'Яндекс',
                    'url' => 'https://yandex.ru/maps/org/123',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $place = Place::query()->where('title', 'Новая точка')->first();

    expect($place)->not->toBeNull()
        ->and($place->user_id)->toBe($owner->id)
        ->and($place->platforms)->toBe([
            ['type' => 'yandex', 'url' => 'https://yandex.ru/maps/org/123', 'label' => 'Яндекс'],
        ]);
});

it('ViewPlace toggle_activation выключает активную точку', function (): void {
    $place = Place::factory()->create(['is_active' => true]);

    Livewire::test(ViewPlace::class, ['record' => $place->getRouteKey()])
        ->callAction('toggle_activation')
        ->assertNotified();

    expect($place->refresh()->is_active)->toBeFalse();
});

it('ViewPlace toggle_activation включает выключенную точку', function (): void {
    $place = Place::factory()->create(['is_active' => false]);

    Livewire::test(ViewPlace::class, ['record' => $place->getRouteKey()])
        ->callAction('toggle_activation')
        ->assertNotified();

    expect($place->refresh()->is_active)->toBeTrue();
});

it('ViewPlace preview_qr открывает модалку без ошибок', function (): void {
    $qr = Mockery::mock(new QrCodeService);
    $qr->shouldReceive('placeScanUrl')->andReturn('https://cafe.otziv.space/s/test');
    $qr->shouldReceive('pngBytes')->andReturn("\x89PNG\r\n\x1a\n");
    app()->instance(QrCodeService::class, $qr);

    $owner = User::factory()->create(['subdomain_slug' => 'qr-test']);
    $place = Place::factory()->for($owner)->create(['title' => 'QR Place']);

    Livewire::test(ViewPlace::class, ['record' => $place->getRouteKey()])
        ->mountAction('preview_qr')
        ->assertActionMounted('preview_qr');
});

it('ViewPlace download_qr отдаёт PNG', function (): void {
    $qr = Mockery::mock(new QrCodeService);
    $qr->shouldReceive('placeScanUrl')->andReturn('https://cafe.otziv.space/s/test');
    $qr->shouldReceive('pngBytes')->andReturn("\x89PNG\r\n\x1a\n");
    app()->instance(QrCodeService::class, $qr);

    $owner = User::factory()->create(['subdomain_slug' => 'dl-test']);
    $place = Place::factory()->for($owner)->create();

    Livewire::test(ViewPlace::class, ['record' => $place->getRouteKey()])
        ->callAction('download_qr')
        ->assertSuccessful();
});

it('ListPlaces bulk_deactivate выключает выбранные точки', function (): void {
    $active1 = Place::factory()->create(['is_active' => true]);
    $active2 = Place::factory()->create(['is_active' => true]);

    Livewire::test(ListPlaces::class)
        ->callTableBulkAction('bulk_deactivate', [$active1, $active2])
        ->assertNotified();

    expect($active1->refresh()->is_active)->toBeFalse()
        ->and($active2->refresh()->is_active)->toBeFalse();
});

it('ListPlaces bulk_activate включает выбранные точки', function (): void {
    $inactive1 = Place::factory()->create(['is_active' => false]);
    $inactive2 = Place::factory()->create(['is_active' => false]);

    Livewire::test(ListPlaces::class)
        ->callTableBulkAction('bulk_activate', [$inactive1, $inactive2])
        ->assertNotified();

    expect($inactive1->refresh()->is_active)->toBeTrue()
        ->and($inactive2->refresh()->is_active)->toBeTrue();
});

it('ListPlaces callTableAction toggle_activation переключает точку', function (): void {
    $place = Place::factory()->create(['is_active' => true]);

    Livewire::test(ListPlaces::class)
        ->callTableAction('toggle_activation', $place)
        ->assertNotified();

    expect($place->refresh()->is_active)->toBeFalse();
});
