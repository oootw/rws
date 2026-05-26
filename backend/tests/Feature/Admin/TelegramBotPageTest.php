<?php

declare(strict_types=1);

use App\Filament\Pages\TelegramBotPage;
use App\Interface\Filament\Auth\AdminUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use SergiX44\Nutgram\Nutgram;

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

it('страница TelegramBot открывается без падения, даже если Nutgram кинул исключение', function (): void {
    Cache::flush();

    // Подменяем Nutgram на бросающий мок — страница должна показать loadError,
    // а не 500.
    $this->app->bind(Nutgram::class, function (): never {
        throw new RuntimeException('telegram offline');
    });

    Livewire::test(TelegramBotPage::class)
        ->assertSet('loadError', 'telegram offline')
        ->assertSet('me', [])
        ->assertSet('webhook', []);
});
