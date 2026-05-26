<?php

declare(strict_types=1);

use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationCommand;
use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationHandler;
use App\Application\Places\DeletePlace\DeletePlaceCommand;
use App\Application\Places\DeletePlace\DeletePlaceHandler;
use App\Application\Places\Exceptions\PlaceNotFound;
use App\Application\Places\UpdatePlace\UpdatePlaceCommand;
use App\Application\Places\UpdatePlace\UpdatePlaceHandler;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
        'guardreviews.admin.name' => 'Test Dev',
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Test Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );
});

// — HTTP smoke на страницы ресурса —

it('показывает список точек', function (): void {
    Place::factory()->count(3)->sequence(
        ['title' => 'Альфа кафе'],
        ['title' => 'Бета бар'],
        ['title' => 'Гамма гриль'],
    )->create();

    $this->get('/admin/places')
        ->assertOk()
        ->assertSee('Альфа кафе')
        ->assertSee('Бета бар')
        ->assertSee('Гамма гриль');
});

it('открывает страницу создания точки', function (): void {
    $this->get('/admin/places/create')->assertOk();
});

it('открывает карточку точки', function (): void {
    $place = Place::factory()->create(['title' => 'Карточка точки']);

    $this->get("/admin/places/{$place->id}")
        ->assertOk()
        ->assertSee('Карточка точки');
});

it('открывает форму редактирования', function (): void {
    $place = Place::factory()->create(['title' => 'Editable']);

    $this->get("/admin/places/{$place->id}/edit")
        ->assertOk()
        ->assertSee('Editable');
});

// — Интеграционные проверки use cases поверх реального Eloquent —

it('UpdatePlaceHandler пишет изменения в БД через репозиторий', function (): void {
    $place = Place::factory()->create([
        'title' => 'Старое',
        'background_image_url' => null,
    ]);

    app(UpdatePlaceHandler::class)->handle(new UpdatePlaceCommand(
        placeId: (string) $place->id,
        title: 'Новое название',
        platforms: [
            ['type' => 'yandex', 'url' => 'https://yandex.ru/x', 'label' => 'Яндекс'],
        ],
        backgroundImageUrl: 'https://cdn.test/bg.png',
    ));

    $place->refresh();

    expect($place->title)->toBe('Новое название')
        ->and($place->background_image_url)->toBe('https://cdn.test/bg.png')
        ->and($place->platforms)->toBe([
            ['type' => 'yandex', 'url' => 'https://yandex.ru/x', 'label' => 'Яндекс'],
        ]);
});

it('ChangePlaceActivationHandler переключает is_active', function (): void {
    $place = Place::factory()->create(['is_active' => true]);

    app(ChangePlaceActivationHandler::class)->handle(new ChangePlaceActivationCommand(
        placeId: (string) $place->id,
        active: false,
    ));

    expect($place->refresh()->is_active)->toBeFalse();

    app(ChangePlaceActivationHandler::class)->handle(new ChangePlaceActivationCommand(
        placeId: (string) $place->id,
        active: true,
    ));

    expect($place->refresh()->is_active)->toBeTrue();
});

it('DeletePlaceHandler каскадно удаляет отзывы точки', function (): void {
    $user = User::factory()->create();
    $place = Place::factory()->for($user)->create();
    Review::query()->create([
        'id' => '11111111-1111-1111-1111-111111111111',
        'place_id' => $place->id,
        'stars' => 2,
        'text' => 'плохо',
        'contact' => 'x@test.local',
        'status' => 'new',
    ]);

    app(DeletePlaceHandler::class)->handle(
        new DeletePlaceCommand(placeId: (string) $place->id),
    );

    expect(Place::query()->whereKey($place->id)->exists())->toBeFalse()
        ->and(Review::query()->where('place_id', $place->id)->exists())->toBeFalse();
});

it('DeletePlaceHandler идемпотентен: повторный вызов бросает PlaceNotFound', function (): void {
    $place = Place::factory()->create();
    $handler = app(DeletePlaceHandler::class);

    $handler->handle(new DeletePlaceCommand(placeId: (string) $place->id));

    expect(fn () => $handler->handle(new DeletePlaceCommand(placeId: (string) $place->id)))
        ->toThrow(PlaceNotFound::class);
});

it('UpdatePlaceHandler с несуществующим placeId бросает PlaceNotFound', function (): void {
    expect(fn () => app(UpdatePlaceHandler::class)->handle(
        new UpdatePlaceCommand(
            placeId: '00000000-0000-0000-0000-000000000000',
            title: 'X',
            platforms: [],
            backgroundImageUrl: null,
        ),
    ))->toThrow(PlaceNotFound::class);
});
