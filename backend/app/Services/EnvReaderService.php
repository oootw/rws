<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Парсит .env-файл в plain-массив для отображения в админ-панели.
 *
 * Намеренно простой парсер: только KEY=value-строки, кавычки снимаются,
 * комментарии и пустые строки игнорируются. Inline-комментарии после
 * значения тоже срезаются. Использовать только для просмотра — не для
 * перегенерации .env.
 */
final class EnvReaderService
{
    /**
     * @return array<string, string>  пары ключ → значение в порядке файла
     */
    public function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $contents = (string) file_get_contents($path);
        $result = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim((string) $line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $eq = strpos($line, '=');

            if ($eq === false) {
                continue;
            }

            $key = trim(substr($line, 0, $eq));
            $value = $this->normalizeValue(substr($line, $eq + 1));

            if ($key !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function normalizeValue(string $raw): string
    {
        $raw = trim($raw);

        // Inline-комментарий после значения вне кавычек.
        if (! str_starts_with($raw, '"') && ! str_starts_with($raw, "'")) {
            $hash = strpos($raw, ' #');
            if ($hash !== false) {
                $raw = rtrim(substr($raw, 0, $hash));
            }
        }

        if (mb_strlen($raw) >= 2) {
            $first = $raw[0];
            $last = $raw[mb_strlen($raw) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $raw = substr($raw, 1, -1);
            }
        }

        return $raw;
    }
}
