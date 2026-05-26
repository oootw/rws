<?php

/*
| PHPUnit bootstrap.
|
| Docker injects backend/.env into the container environment; PHPUnit 12 only
| overwrites existing vars when force="true", but Laravel's Env repository
| may already be seeded before PhpHandler runs in some Pest paths.
| Re-apply testing env here so Feature tests always get sqlite :memory:.
*/

$testingEnv = [
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:AckfSECXIvnK5r28GVIWUAxmbBSjTsmF11WBzy3uoaA=',
    'APP_LOCALE' => 'ru',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'PULSE_ENABLED' => 'false',
    'TELESCOPE_ENABLED' => 'false',
    'NIGHTWATCH_ENABLED' => 'false',
];

foreach ($testingEnv as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require __DIR__.'/../vendor/autoload.php';
