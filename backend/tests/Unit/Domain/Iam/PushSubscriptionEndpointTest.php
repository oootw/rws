<?php

declare(strict_types=1);

use App\Domain\Iam\PushSubscriptionEndpoint;

it('принимает валидный https endpoint', function (): void {
    $endpoint = new PushSubscriptionEndpoint('https://fcm.googleapis.com/fcm/send/abc123');

    expect($endpoint->value)->toBe('https://fcm.googleapis.com/fcm/send/abc123');
});

it('бросает ошибку на пустую строку', function (): void {
    new PushSubscriptionEndpoint('');
})->throws(InvalidArgumentException::class);

it('бросает ошибку на не-https URL', function (): void {
    new PushSubscriptionEndpoint('http://example.com/push/x');
})->throws(InvalidArgumentException::class);

it('бросает ошибку на невалидный URL', function (): void {
    new PushSubscriptionEndpoint('https:// not a url');
})->throws(InvalidArgumentException::class);

it('бросает ошибку при превышении 2048 символов', function (): void {
    new PushSubscriptionEndpoint('https://example.com/'.str_repeat('a', 2048));
})->throws(InvalidArgumentException::class);

it('сравнивает endpoint-ы по значению', function (): void {
    $a = new PushSubscriptionEndpoint('https://example.com/push/1');
    $b = new PushSubscriptionEndpoint('https://example.com/push/1');
    $c = new PushSubscriptionEndpoint('https://example.com/push/2');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
