<?php

declare(strict_types=1);

namespace App\Application\Payments\Exceptions;

use RuntimeException;

final class PaymentTransactionNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("Платёжная транзакция {$id} не найдена.");
    }
}
