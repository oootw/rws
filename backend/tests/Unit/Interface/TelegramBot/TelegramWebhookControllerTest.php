<?php

declare(strict_types=1);

use App\Interface\Http\Controllers\Webhook\TelegramWebhookController;
use Illuminate\Http\Response;
use SergiX44\Nutgram\Nutgram;
use Tests\TestCase;

uses(TestCase::class);

it('возвращает OK после запуска бота', function (): void {
    $bot = Mockery::mock(Nutgram::class);
    $bot->shouldReceive('run')->once();

    $response = (new TelegramWebhookController)->__invoke($bot);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getContent())->toBe('OK');
});
