<?php

declare(strict_types=1);

use App\Services\EnvReaderService;

function tempEnv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'env-test-');
    file_put_contents((string) $path, $contents);

    return (string) $path;
}

it('парсит KEY=value-строки', function (): void {
    $path = tempEnv("APP_ENV=local\nDB_HOST=127.0.0.1\n");

    expect((new EnvReaderService)->read($path))
        ->toBe(['APP_ENV' => 'local', 'DB_HOST' => '127.0.0.1']);
});

it('игнорирует пустые строки и комментарии', function (): void {
    $path = tempEnv("\n# top comment\nAPP_ENV=local\n  \n# tail\n");

    expect((new EnvReaderService)->read($path))
        ->toBe(['APP_ENV' => 'local']);
});

it('снимает двойные и одинарные кавычки', function (): void {
    $path = tempEnv("FOO=\"hello world\"\nBAR='val'\n");

    expect((new EnvReaderService)->read($path))
        ->toBe(['FOO' => 'hello world', 'BAR' => 'val']);
});

it('срезает inline-комментарии после значения без кавычек', function (): void {
    $path = tempEnv("APP_ENV=local # local dev\nFOO=bar\n");

    expect((new EnvReaderService)->read($path))
        ->toBe(['APP_ENV' => 'local', 'FOO' => 'bar']);
});

it('возвращает пустой массив, если файл не существует', function (): void {
    expect((new EnvReaderService)->read('/nonexistent/path/.env'))->toBe([]);
});
