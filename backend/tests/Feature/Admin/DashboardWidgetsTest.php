<?php

declare(strict_types=1);

use App\Domain\Analytics\ActionType;
use App\Domain\Payments\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Filament\Widgets\OperationalStatsWidget;
use App\Filament\Widgets\OwnersStatsWidget;
use App\Filament\Widgets\RecentReviewsWidget;
use App\Filament\Widgets\ReviewsStatsWidget;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\ActionLog;
use App\Models\PaymentTransaction;
use App\Models\Place;
use App\Models\Review;
use App\Models\Tariff;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

it('OwnersStatsWidget рендерится со счётчиками', function (): void {
    User::factory()->count(2)->create();
    User::factory()->create(['subscription_ends_at' => now()->addDays(10)]);

    Livewire::test(OwnersStatsWidget::class)->assertSuccessful();
});

it('ReviewsStatsWidget учитывает статусы', function (): void {
    Review::factory()->create(['status' => ReviewStatus::New]);
    Review::factory()->create(['status' => ReviewStatus::Resolved]);

    Livewire::test(ReviewsStatsWidget::class)
        ->assertSuccessful()
        ->assertSeeText('Новые')
        ->assertSeeText('Решено');
});

it('OperationalStatsWidget считает скан/негатив/упавшие/платежи', function (): void {
    $place = Place::factory()->create();

    foreach ([ActionType::Scanned, ActionType::Scanned, ActionType::LeftNegative] as $type) {
        ActionLog::query()->create([
            'id' => (string) Str::uuid(),
            'place_id' => $place->id,
            'action_type' => $type->value,
            'metadata' => null,
            'created_at' => now(),
        ]);
    }

    $tariff = Tariff::factory()->create();
    $owner = User::factory()->withTariff($tariff)->create();
    PaymentTransaction::factory()->create([
        'user_id' => $owner->id,
        'tariff_id' => $tariff->id,
        'status' => PaymentStatus::Success,
    ]);

    Livewire::test(OperationalStatsWidget::class)
        ->assertSuccessful()
        ->assertSeeText('Сканов за 7 дней')
        ->assertSeeText('Платежи за 7 дней');
});

it('RecentReviewsWidget показывает последние отзывы', function (): void {
    $place = Place::factory()->create(['title' => 'WidgetPlace']);
    Review::factory()->for($place)->create(['text' => 'WidgetReviewText']);

    Livewire::test(RecentReviewsWidget::class)
        ->assertSuccessful()
        ->assertSeeText('WidgetReviewText');
});
