<?php

declare(strict_types=1);

namespace App\Application\Shared\Transactions;

use Closure;

/**
 * Порт для запуска кода в одной БД-транзакции. Use cases используют его,
 * чтобы не дёргать DB::transaction напрямую и оставаться независимыми
 * от Laravel/Eloquent.
 */
interface TransactionRunner
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed;
}
