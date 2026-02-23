<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->confirmed(),
            'user_id' => User::factory()->customer(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'status' => 'success',
        ];
    }

    public function success(): static
    {
        return $this->state(['status' => 'success']);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }

    public function refunded(): static
    {
        return $this->state(['status' => 'refunded']);
    }
}
