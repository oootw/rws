<?php

declare(strict_types=1);

use App\Domain\Iam\TelegramChatId;

it('принимает отрицательный id обычной группы', function (): void {
    expect(new TelegramChatId('-123456789')->value)->toBe('-123456789');
});

it('принимает длинный отрицательный id супергруппы', function (): void {
    expect(new TelegramChatId('-1001234567890')->value)->toBe('-1001234567890');
});

it('сравнивает два VO по значению', function (): void {
    $a = new TelegramChatId('-100500');
    $b = new TelegramChatId('-100500');
    $c = new TelegramChatId('-100501');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});

it('отклоняет пустую строку', function (): void {
    new TelegramChatId('');
})->throws(InvalidArgumentException::class);

it('отклоняет положительное число (это id пользователя, не группы)', function (): void {
    new TelegramChatId('123456789');
})->throws(InvalidArgumentException::class);

it('отклоняет нечисловой формат', function (): void {
    new TelegramChatId('-abc');
})->throws(InvalidArgumentException::class);

it('отклоняет лидирующие пробелы', function (): void {
    new TelegramChatId(' -100500');
})->throws(InvalidArgumentException::class);
