<?php

namespace Database\Factories;

use App\Domain\Payments\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tariff_id' => Tariff::factory(),
            'amount' => 99000,
            'external_id' => null,
            'status' => PaymentStatus::Pending,
        ];
    }
}
