<?php

declare(strict_types=1);

use App\Filament\Resources\Places\Pages\ListPlaces;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\Place;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
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

it('SelectFilter по владельцу показывает только его точки', function (): void {
    $ownerA = User::factory()->create(['name' => 'Owner A']);
    $ownerB = User::factory()->create(['name' => 'Owner B']);

    $placeA = Place::factory()->for($ownerA)->create(['title' => 'A1']);
    $placeB = Place::factory()->for($ownerB)->create(['title' => 'B1']);

    Livewire::test(ListPlaces::class)
        ->assertCanSeeTableRecords([$placeA, $placeB])
        ->filterTable('user_id', $ownerA->id)
        ->assertCanSeeTableRecords([$placeA])
        ->assertCanNotSeeTableRecords([$placeB]);
});

it('TernaryFilter true показывает только активные точки', function (): void {
    $active = Place::factory()->create(['title' => 'Active', 'is_active' => true]);
    $inactive = Place::factory()->create(['title' => 'Inactive', 'is_active' => false]);

    Livewire::test(ListPlaces::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

it('TernaryFilter false показывает только выключенные', function (): void {
    $active = Place::factory()->create(['is_active' => true]);
    $inactive = Place::factory()->create(['is_active' => false]);

    Livewire::test(ListPlaces::class)
        ->filterTable('is_active', false)
        ->assertCanSeeTableRecords([$inactive])
        ->assertCanNotSeeTableRecords([$active]);
});

it('TernaryFilter null показывает все точки', function (): void {
    $active = Place::factory()->create(['is_active' => true]);
    $inactive = Place::factory()->create(['is_active' => false]);

    Livewire::test(ListPlaces::class)
        ->assertCanSeeTableRecords([$active, $inactive]);
});

it('таблица показывает ключевые колонки', function (): void {
    $owner = User::factory()->create(['name' => 'Колумба']);
    Place::factory()->for($owner)->create(['title' => 'CheckTitle']);

    Livewire::test(ListPlaces::class)
        ->assertSeeText('CheckTitle')
        ->assertSeeText('Колумба');
});
