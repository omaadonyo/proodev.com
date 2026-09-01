<?php

namespace Database\Factories;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => PaymentPurpose::Credits,
            'amount' => 8,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
            'provider' => 'manual',
        ];
    }

    public function verification(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => PaymentPurpose::Verification,
            'amount' => 8,
        ]);
    }

    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => PaymentPurpose::Subscription,
            'amount' => 499,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
