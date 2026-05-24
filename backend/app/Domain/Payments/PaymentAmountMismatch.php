<?php

declare(strict_types=1);

namespace App\Domain\Payments;

use DomainException;

final class PaymentAmountMismatch extends DomainException
{
    public function __construct()
    {
        parent::__construct('Сумма уведомления от эквайера не совпадает с суммой транзакции.');
    }
}
