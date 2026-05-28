<?php

declare(strict_types=1);

use App\Domain\Iam\Email;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Notifications\OwnerContact;
use App\Domain\Places\PlatformLink;
use App\Domain\Places\PlatformType;
use App\Domain\Places\Title;
use App\Domain\Reviews\ContactInfo;
use App\Domain\Reviews\ReviewText;
use App\Services\Captcha\NullCaptchaVerifier;

it('принимает валидное название точки', function (): void {
    expect(new Title('Кафе')->value)->toBe('Кафе');
});

it('отклоняет пустое название точки', function (): void {
    new Title('');
})->throws(InvalidArgumentException::class);

it('отклоняет слишком длинное название точки', function (): void {
    new Title(str_repeat('a', 256));
})->throws(InvalidArgumentException::class);

it('принимает валидный email', function (): void {
    expect(new Email('owner@example.com')->value)->toBe('owner@example.com');
});

it('отклоняет некорректный email', function (): void {
    new Email('bad-email');
})->throws(InvalidArgumentException::class);

it('валидирует контакт и текст отзыва', function (): void {
    expect(new ContactInfo('test@mail.ru')->value)->toBe('test@mail.ru')
        ->and(new ReviewText('Жалоба')->value)->toBe('Жалоба');
});

it('отклоняет слишком длинный текст отзыва', function (): void {
    new ReviewText(str_repeat('a', 5001));
})->throws(InvalidArgumentException::class);

it('отклоняет пустой текст отзыва', function (): void {
    new ReviewText('');
})->throws(InvalidArgumentException::class);

it('отклоняет слишком длинный контакт', function (): void {
    new ContactInfo(str_repeat('a', 256));
})->throws(InvalidArgumentException::class);

it('отклоняет пустой контакт', function (): void {
    new ContactInfo('');
})->throws(InvalidArgumentException::class);

it('валидирует ссылку на площадку', function (): void {
    $link = new PlatformLink(
        type: PlatformType::Yandex,
        url: 'https://yandex.ru/maps',
        label: 'Яндекс',
    );

    expect($link->url)->toBe('https://yandex.ru/maps');

    new PlatformLink(PlatformType::Custom, '', 'label');
})->throws(InvalidArgumentException::class);

it('отклоняет зарезервированный адрес поддомена', function (): void {
    new SubdomainSlug('admin');
})->throws(InvalidArgumentException::class);

it('определяет наличие каналов у контакта владельца', function (): void {
    expect((new OwnerContact(null, null, null))->hasAnyChannel())->toBeFalse()
        ->and((new OwnerContact('1', null, null))->hasAnyChannel())->toBeTrue()
        ->and((new OwnerContact(null, null, 'mail@test.ru'))->hasAnyChannel())->toBeTrue()
        ->and((new OwnerContact(null, null, null, telegramChatIds: ['-100500']))->hasAnyChannel())->toBeTrue();
});

it('hasAnyTelegramTarget учитывает DM и групповые чаты', function (): void {
    expect((new OwnerContact(null, null, null))->hasAnyTelegramTarget())->toBeFalse()
        ->and((new OwnerContact(null, null, 'mail@test.ru'))->hasAnyTelegramTarget())->toBeFalse()
        ->and((new OwnerContact('1', null, null))->hasAnyTelegramTarget())->toBeTrue()
        ->and((new OwnerContact(null, null, null, telegramChatIds: ['-100500']))->hasAnyTelegramTarget())->toBeTrue()
        ->and((new OwnerContact('1', null, null, telegramChatIds: ['-100500']))->hasAnyTelegramTarget())->toBeTrue();
});

it('заглушка captcha принимает любой непустой токен', function (): void {
    $verifier = new NullCaptchaVerifier;

    expect($verifier->verify('token'))->toBeTrue()
        ->and($verifier->verify(''))->toBeFalse();
});
