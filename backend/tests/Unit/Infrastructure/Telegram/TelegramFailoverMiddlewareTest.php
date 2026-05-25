<?php

declare(strict_types=1);

use App\Infrastructure\Telegram\TelegramFailoverMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

function makeClient(MockHandler $mock, array $urls): Client
{
    $stack = HandlerStack::create($mock);
    $stack->push(new TelegramFailoverMiddleware($urls, new NullLogger), 'failover');

    return new Client(['handler' => $stack]);
}

function recordingMockHandler(array $responses): array
{
    $mock = new MockHandler($responses);
    $hits = [];
    $stack = HandlerStack::create($mock);

    return [$mock, $hits];
}

it('идёт по первому URL, если он отвечает 200', function (): void {
    $mock = new MockHandler([new Response(200, [], '{"ok":true}')]);
    $hosts = [];
    $mock = new MockHandler([
        function ($request, $opts) use (&$hosts) {
            $hosts[] = $request->getUri()->getHost();

            return new Response(200, [], '{"ok":true}');
        },
    ]);

    $client = makeClient($mock, ['https://primary.example.com', 'https://backup.example.com']);
    $client->get('/bot123/getMe');

    expect($hosts)->toBe(['primary.example.com']);
});

it('переключается на следующий URL при ошибке соединения', function (): void {
    $hosts = [];
    $calls = 0;

    $mock = new MockHandler([
        function ($request, $opts) use (&$hosts, &$calls) {
            $hosts[] = $request->getUri()->getHost();
            $calls++;

            return new ConnectException('connect timeout', $request);
        },
        function ($request, $opts) use (&$hosts, &$calls) {
            $hosts[] = $request->getUri()->getHost();
            $calls++;

            return new Response(200, [], '{"ok":true}');
        },
    ]);

    $client = makeClient($mock, ['https://primary.example.com', 'https://backup.example.com']);
    $response = $client->get('/bot123/sendMessage');

    expect($response->getStatusCode())->toBe(200)
        ->and($hosts)->toBe(['primary.example.com', 'backup.example.com'])
        ->and($calls)->toBe(2);
});

it('переключается на следующий URL при 502 от текущего', function (): void {
    $hosts = [];

    $mock = new MockHandler([
        function ($request, $opts) use (&$hosts) {
            $hosts[] = $request->getUri()->getHost();

            return new Response(502, [], 'bad gateway');
        },
        function ($request, $opts) use (&$hosts) {
            $hosts[] = $request->getUri()->getHost();

            return new Response(200, [], '{"ok":true}');
        },
    ]);

    $client = makeClient($mock, ['https://primary.example.com', 'https://backup.example.com']);
    $response = $client->get('/bot123/sendMessage');

    expect($response->getStatusCode())->toBe(200)
        ->and($hosts)->toBe(['primary.example.com', 'backup.example.com']);
});

it('не переключается на 401 (бизнес-ошибка Telegram)', function (): void {
    $hosts = [];

    $mock = new MockHandler([
        function ($request, $opts) use (&$hosts) {
            $hosts[] = $request->getUri()->getHost();

            return new Response(401, [], '{"ok":false,"error_code":401}');
        },
    ]);

    $client = makeClient($mock, ['https://primary.example.com', 'https://backup.example.com']);
    $response = $client->get('/bot123/sendMessage', ['http_errors' => false]);

    expect($response->getStatusCode())->toBe(401)
        ->and($hosts)->toBe(['primary.example.com']);  // backup не дёрнули
});

it('бросает исключение, если все URL пула упали', function (): void {
    $mock = new MockHandler([
        function ($request) {
            return new ConnectException('down', $request);
        },
        function ($request) {
            return new ConnectException('down', $request);
        },
    ]);

    $client = makeClient($mock, ['https://primary.example.com', 'https://backup.example.com']);
    $client->get('/bot123/sendMessage');
})->throws(ConnectException::class);
