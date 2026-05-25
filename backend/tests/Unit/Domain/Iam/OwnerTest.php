<?php

declare(strict_types=1);

use App\Domain\Iam\Email;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\Subscription;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TelegramId;

/**
 * Pure-unit на агрегат Owner. Никаких репозиториев и контейнера.
 * Цель — зафиксировать инварианты и поведение мутаций, добавленных
 * в Фазе 1 (changeProfile/overrideSubscription/changeTariff/subdomainEquals).
 */
function freshOwnerWithSubscription(): Owner
{
    $owner = Owner::register(
        id: new OwnerId('11111111-1111-1111-1111-111111111111'),
        name: 'Иван',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug('cafe'),
        telegramId: new TelegramId('1001'),
        tariffId: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
    );

    $owner->extendSubscription(30, new DateTimeImmutable('2026-06-01T00:00:00Z'));

    return $owner;
}

it('register заводит владельца без подписки', function (): void {
    $owner = Owner::register(
        id: new OwnerId('11111111-1111-1111-1111-111111111111'),
        name: 'Иван',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug('cafe'),
        telegramId: null,
        tariffId: null,
    );

    expect($owner->subscription()->endsAt)->toBeNull()
        ->and($owner->telegramId())->toBeNull()
        ->and($owner->tariffId())->toBeNull();
});

it('changeProfile меняет все указанные поля', function (): void {
    $owner = freshOwnerWithSubscription();
    $originalSubscriptionEndsAt = $owner->subscription()->endsAt;

    $owner->changeProfile(
        name: 'Пётр',
        email: new Email('new@example.com'),
        subdomain: new SubdomainSlug('newcafe'),
        telegramId: new TelegramId('9999'),
        tariffId: new TariffId('dddddddd-dddd-dddd-dddd-dddddddddddd'),
    );

    expect($owner->name())->toBe('Пётр')
        ->and($owner->email()->value)->toBe('new@example.com')
        ->and($owner->subdomain()->value)->toBe('newcafe')
        ->and($owner->telegramId()?->value)->toBe('9999')
        ->and($owner->tariffId()?->value)->toBe('dddddddd-dddd-dddd-dddd-dddddddddddd')
        ->and($owner->subscription()->endsAt)->toEqual($originalSubscriptionEndsAt);
});

it('changeProfile допускает null telegramId и tariffId', function (): void {
    $owner = freshOwnerWithSubscription();

    $owner->changeProfile(
        name: 'Иван',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug('cafe'),
        telegramId: null,
        tariffId: null,
    );

    expect($owner->telegramId())->toBeNull()
        ->and($owner->tariffId())->toBeNull();
});

it('overrideSubscription выставляет произвольную дату', function (): void {
    $owner = freshOwnerWithSubscription();
    $newEnds = new DateTimeImmutable('2030-01-01T00:00:00Z');

    $owner->overrideSubscription($newEnds);

    expect($owner->subscription()->endsAt)->toEqual($newEnds);
});

it('overrideSubscription с null сбрасывает подписку', function (): void {
    $owner = freshOwnerWithSubscription();

    $owner->overrideSubscription(null);

    expect($owner->subscription())->toEqual(Subscription::none())
        ->and($owner->subscription()->endsAt)->toBeNull();
});

it('overrideSubscription НЕ продлевает (как extendSubscription), а ставит точную дату', function (): void {
    // У владельца подписка до 2026-07-01 (30 дней от 2026-06-01).
    $owner = freshOwnerWithSubscription();
    $expected = new DateTimeImmutable('2026-06-15T00:00:00Z');

    $owner->overrideSubscription($expected);

    // Должна быть ровно 06-15, а не 07-01 + 14 дней.
    expect($owner->subscription()->endsAt?->format('Y-m-d'))->toBe('2026-06-15');
});

it('changeTariff присваивает новый тариф', function (): void {
    $owner = freshOwnerWithSubscription();
    $newTariff = new TariffId('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee');

    $owner->changeTariff($newTariff);

    expect($owner->tariffId()?->value)->toBe($newTariff->value);
});

it('changeTariff с null отвязывает тариф', function (): void {
    $owner = freshOwnerWithSubscription();

    $owner->changeTariff(null);

    expect($owner->tariffId())->toBeNull();
});

it('subdomainEquals корректно сравнивает по значению, не по объекту', function (): void {
    $owner = freshOwnerWithSubscription();

    expect($owner->subdomainEquals(new SubdomainSlug('cafe')))->toBeTrue()
        ->and($owner->subdomainEquals(new SubdomainSlug('other')))->toBeFalse();
});

it('hasActiveSubscriptionAt отражает текущее состояние подписки', function (): void {
    $owner = freshOwnerWithSubscription();

    expect($owner->hasActiveSubscriptionAt(new DateTimeImmutable('2026-06-15T00:00:00Z')))->toBeTrue()
        ->and($owner->hasActiveSubscriptionAt(new DateTimeImmutable('2026-08-01T00:00:00Z')))->toBeFalse();
});

it('asNotificationContact собирает все каналы владельца', function (): void {
    $owner = freshOwnerWithSubscription();

    $contact = $owner->asNotificationContact();

    expect($contact->telegramId)->toBe('1001')
        ->and($contact->email)->toBe('owner@example.com')
        ->and($contact->maxId)->toBeNull();
});

it('scanBaseUrl собирает корректный URL поддомена', function (): void {
    $owner = freshOwnerWithSubscription();

    expect($owner->scanBaseUrl('otziv.space'))->toBe('https://cafe.otziv.space');
});
