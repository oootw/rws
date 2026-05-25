<?php

declare(strict_types=1);

use App\Interface\TelegramBot\Support\TelegramMessages;
use Tests\TestCase;

uses(TestCase::class);

it('возвращает главное меню с командами бота', function (): void {
    $menu = TelegramMessages::mainMenu();

    expect($menu)
        ->toContain('/places')
        ->toContain('/addplace')
        ->toContain('/reviews')
        ->toContain('/subscription')
        ->toContain('/pay')
        ->toContain('/link');
});

it('сообщает что подписка не активна без даты окончания', function (): void {
    expect(TelegramMessages::subscriptionStatus(null))
        ->toContain('не активна')
        ->toContain('/pay');
});

it('сообщает что подписка не активна после истечения', function (): void {
    $expired = new DateTimeImmutable('-1 day');

    expect(TelegramMessages::subscriptionStatus($expired))
        ->toContain('не активна');
});

it('сообщает дату активной подписки в московском времени', function (): void {
    $endsAt = new DateTimeImmutable('2030-06-15 12:00:00 UTC');

    expect(TelegramMessages::subscriptionStatus($endsAt))
        ->toContain('активна до 15.06.2030')
        ->toContain('МСК');
});
