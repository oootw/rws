<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Tinkoff;

/**
 * Подпись запросов/уведомлений Tinkoff Acquiring API.
 *
 * Алгоритм: только скалярные значения корневого объекта (без Token),
 * + поле Password, ksort, конкатенация значений, sha256.
 */
final class TinkoffTokenSigner
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function sign(array $params, string $password): string
    {
        $values = $this->rootScalarValues($params);
        $values['Password'] = $password;
        ksort($values);

        return hash('sha256', implode('', array_values($values)));
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function matches(array $params, string $password, string $token): bool
    {
        return hash_equals($this->sign($params, $password), $token);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, string>
     */
    private function rootScalarValues(array $params): array
    {
        $values = [];

        foreach ($params as $key => $value) {
            if ($key === 'Token' || is_array($value)) {
                continue;
            }

            if (is_bool($value)) {
                $values[$key] = $value ? 'true' : 'false';
            } else {
                $values[$key] = (string) $value;
            }
        }

        return $values;
    }
}
