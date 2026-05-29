<?php

declare(strict_types=1);

use App\Domain\Iam\Feature;
use App\Models\OwnerTelegramChat;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['guardreviews.telegram.bot_username' => 'GuardReviewsBot']);
});

function ownerWithSharedChatFeature(): User
{
    $tariff = Tariff::factory()->create([
        'features' => [Feature::SharedTelegramChat->value],
    ]);

    return User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
}

it('happy path: issue-link → bind в БД → index → delete', function (): void {
    $owner = ownerWithSharedChatFeature();
    loginAsOwner($owner);

    // 1. Запрос deep-link.
    $issued = $this->postJson('/api/owner/telegram-chats/issue-link', [], tenantHeaders($owner))
        ->assertCreated()
        ->assertJsonStructure(['data' => ['deep_link', 'expires_at']]);

    expect($issued->json('data.deep_link'))->toStartWith('https://t.me/GuardReviewsBot?startgroup=');

    // 2. Имитируем результат привязки ботом (фаза A3) — запись в owner_telegram_chats.
    $row = OwnerTelegramChat::query()->create([
        'owner_id' => $owner->id,
        'chat_id' => '-1001234567890',
        'title' => 'Команда',
        'linked_at' => now(),
    ]);

    // 3. Список.
    $this->getJson('/api/owner/telegram-chats', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.chat_id', '-1001234567890')
        ->assertJsonPath('data.0.title', 'Команда');

    // 4. Удаление.
    $this->deleteJson('/api/owner/telegram-chats/'.$row->id, [], tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    expect(OwnerTelegramChat::query()->count())->toBe(0);
});

it('401 без сессии', function (): void {
    $owner = ownerWithSharedChatFeature();

    $this->getJson('/api/owner/telegram-chats', tenantHeaders($owner))->assertUnauthorized();
    $this->postJson('/api/owner/telegram-chats/issue-link', [], tenantHeaders($owner))->assertUnauthorized();
    $this->deleteJson('/api/owner/telegram-chats/any-id', [], tenantHeaders($owner))->assertUnauthorized();
});

it('402 subscription_expired без активной подписки', function (): void {
    $tariff = Tariff::factory()->create([
        'features' => [Feature::SharedTelegramChat->value],
    ]);
    $owner = User::factory()->withoutSubscription()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/telegram-chats/issue-link', [], tenantHeaders($owner))
        ->assertStatus(402)
        ->assertJsonPath('code', 'subscription_expired');
});

it('403 feature_not_available если фича снята с тарифа', function (): void {
    $tariff = Tariff::factory()->create(['features' => []]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
    loginAsOwner($owner);

    $this->getJson('/api/owner/telegram-chats', tenantHeaders($owner))
        ->assertStatus(403)
        ->assertJsonPath('code', 'feature_not_available');

    $this->postJson('/api/owner/telegram-chats/issue-link', [], tenantHeaders($owner))
        ->assertStatus(403)
        ->assertJsonPath('code', 'feature_not_available');
});

it('404 telegram_chat_not_found при попытке удалить чужой чат', function (): void {
    $alice = ownerWithSharedChatFeature();
    $bobTariff = Tariff::factory()->create([
        'features' => [Feature::SharedTelegramChat->value],
    ]);
    $bob = User::factory()->create([
        'subdomain_slug' => 'bar',
        'telegram_id' => '2002',
        'tariff_id' => $bobTariff->id,
    ]);
    $bobChat = OwnerTelegramChat::query()->create([
        'owner_id' => $bob->id,
        'chat_id' => '-1009999999999',
        'title' => 'Чужая команда',
        'linked_at' => now(),
    ]);

    loginAsOwner($alice);

    $this->deleteJson('/api/owner/telegram-chats/'.$bobChat->id, [], tenantHeaders($alice))
        ->assertNotFound()
        ->assertJsonPath('code', 'telegram_chat_not_found');

    expect(OwnerTelegramChat::query()->find($bobChat->id))->not->toBeNull();
});
