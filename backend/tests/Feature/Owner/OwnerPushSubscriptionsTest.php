<?php

declare(strict_types=1);

use App\Models\OwnerPushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function seedOwnerPushSubscription(User $owner, string $endpoint): void
{
    OwnerPushSubscription::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $owner->id,
        'endpoint' => $endpoint,
        'p256dh' => 'p',
        'auth' => 'a',
        'user_agent' => null,
        'created_at' => now(),
        'last_seen_at' => now(),
    ]);
}

beforeEach(function (): void {
    config([
        'services.webpush.public_key' => 'pub-key',
        'services.webpush.private_key' => 'priv-key',
        'services.webpush.subject' => 'mailto:ops@example.com',
    ]);
});

function subscribePayload(string $endpoint = 'https://fcm.googleapis.com/x/abc'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => ['p256dh' => 'p256dh-value', 'auth' => 'auth-value'],
        'user_agent' => 'Chrome/120',
    ];
}

it('GET /push/config отдаёт VAPID public key и enabled=true', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->getJson('/api/owner/push/config', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.vapid_public_key', 'pub-key')
        ->assertJsonPath('data.enabled', true);
});

it('GET /push/config возвращает enabled=false без VAPID', function (): void {
    config([
        'services.webpush.public_key' => '',
        'services.webpush.private_key' => '',
        'services.webpush.subject' => '',
    ]);

    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->getJson('/api/owner/push/config', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.enabled', false);
});

it('POST /push/subscribe регистрирует подписку и она появляется в list', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->postJson('/api/owner/push/subscribe', subscribePayload(), tenantHeaders($owner))
        ->assertCreated();

    $this->getJson('/api/owner/push/subscriptions', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.0.endpoint', 'https://fcm.googleapis.com/x/abc')
        ->assertJsonPath('data.0.user_agent', 'Chrome/120');

    expect(OwnerPushSubscription::query()->where('owner_id', $owner->id)->count())->toBe(1);
});

it('повторный subscribe с тем же endpoint идемпотентен (одна запись, обновлён last_seen_at)', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->postJson('/api/owner/push/subscribe', subscribePayload(), tenantHeaders($owner))->assertCreated();
    $this->postJson('/api/owner/push/subscribe', subscribePayload(), tenantHeaders($owner))->assertCreated();

    expect(OwnerPushSubscription::query()->where('owner_id', $owner->id)->count())->toBe(1);
});

it('POST /push/subscribe возвращает 422 на http endpoint', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->postJson('/api/owner/push/subscribe', subscribePayload('http://insecure/x'), tenantHeaders($owner))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['endpoint']);
});

it('endpoint без сессии → 401/419', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);

    $this->getJson('/api/owner/push/subscriptions', tenantHeaders($owner))
        ->assertStatus(401);
});

it('DELETE /push/subscribe отзывает подписку текущего owner-а', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);
    $this->postJson('/api/owner/push/subscribe', subscribePayload(), tenantHeaders($owner))->assertCreated();

    $this->deleteJson(
        '/api/owner/push/subscribe',
        ['endpoint' => 'https://fcm.googleapis.com/x/abc'],
        tenantHeaders($owner),
    )->assertNoContent();

    expect(OwnerPushSubscription::query()->where('owner_id', $owner->id)->count())->toBe(0);
});

it('DELETE чужого endpoint → 404, чужая подписка не удаляется', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar', 'telegram_id' => '2002']);

    seedOwnerPushSubscription($alice, 'https://fcm.googleapis.com/x/alice');
    loginAsOwner($bob);

    $this->deleteJson(
        '/api/owner/push/subscribe',
        ['endpoint' => 'https://fcm.googleapis.com/x/alice'],
        tenantHeaders($bob),
    )->assertStatus(404)
        ->assertJsonPath('code', 'push_subscription_not_found');

    expect(OwnerPushSubscription::query()->where('endpoint', 'https://fcm.googleapis.com/x/alice')->count())
        ->toBe(1);
});

it('subscribe тем же endpoint от другого owner-а перевыпускает подписку', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar', 'telegram_id' => '2002']);

    seedOwnerPushSubscription($alice, 'https://fcm.googleapis.com/x/abc');
    loginAsOwner($bob);

    $this->postJson('/api/owner/push/subscribe', subscribePayload(), tenantHeaders($bob))->assertCreated();

    $aliceCount = OwnerPushSubscription::query()->where('owner_id', $alice->id)->count();
    $bobCount = OwnerPushSubscription::query()->where('owner_id', $bob->id)->count();

    expect($aliceCount)->toBe(0)->and($bobCount)->toBe(1);
});
