<?php

declare(strict_types=1);

use App\Domain\Iam\Subscription;

it('считает себя активной, если endsAt в будущем', function (): void {
    $sub = new Subscription(new DateTimeImmutable('2026-12-31T00:00:00Z'));

    expect($sub->isActiveAt(new DateTimeImmutable('2026-06-01T00:00:00Z')))->toBeTrue();
});

it('считает себя истёкшей, если endsAt в прошлом', function (): void {
    $sub = new Subscription(new DateTimeImmutable('2026-01-01T00:00:00Z'));

    expect($sub->isActiveAt(new DateTimeImmutable('2026-06-01T00:00:00Z')))->toBeFalse();
});

it('без endsAt всегда неактивна', function (): void {
    expect(Subscription::none()->isActiveAt(new DateTimeImmutable))->toBeFalse();
});

it('продлевает активную подписку от текущей даты окончания', function (): void {
    $sub = new Subscription(new DateTimeImmutable('2026-06-15T00:00:00Z'));

    $extended = $sub->extend(30, new DateTimeImmutable('2026-06-01T00:00:00Z'));

    expect($extended->endsAt?->format('Y-m-d'))->toBe('2026-07-15');
});

it('продлевает истёкшую подписку от текущего момента', function (): void {
    $sub = new Subscription(new DateTimeImmutable('2026-01-01T00:00:00Z'));

    $extended = $sub->extend(30, new DateTimeImmutable('2026-06-01T00:00:00Z'));

    expect($extended->endsAt?->format('Y-m-d'))->toBe('2026-07-01');
});
