<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Маскирует чувствительные значения в строках .env-формата.
 *
 * Решение по ключам — на основе regex по имени переменной: всё, что
 * содержит KEY/SECRET/TOKEN/PASSWORD/HASH/SALT/DSN, считаем секретом.
 * Чистая функция без I/O — легко покрыть unit-тестами.
 */
final class EnvMasker
{
    private const SECRET_PATTERN = '/(?:KEY|SECRET|TOKEN|PASSWORD|HASH|SALT|DSN)/i';

    /**
     * @param  array<string, string>  $env
     * @return array<string, string> значение уже замаскировано (длина оригинала сохранена в подсказке)
     */
    public function mask(array $env): array
    {
        $result = [];

        foreach ($env as $key => $value) {
            $result[$key] = $this->isSecretKey((string) $key)
                ? '••• (length: '.mb_strlen($value).')'
                : $value;
        }

        return $result;
    }

    public function isSecretKey(string $key): bool
    {
        return (bool) preg_match(self::SECRET_PATTERN, $key);
    }
}
