<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['VIP', 'Standard', 'Economy']);

        $priceMap = [
            'VIP' => fake()->randomFloat(2, 150, 500),
            'Standard' => fake()->randomFloat(2, 50, 149),
            'Economy' => fake()->randomFloat(2, 10, 49),
        ];

        return [
            'event_id' => Event::factory(),
            'type' => $type,
            'price' => $priceMap[$type],
            'quantity' => fake()->numberBetween(10, 200),
        ];
    }

    public function vip(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'VIP',
            'price' => fake()->randomFloat(2, 150, 500),
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'Standard',
            'price' => fake()->randomFloat(2, 50, 149),
        ]);
    }

    public function economy(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'Economy',
            'price' => fake()->randomFloat(2, 10, 49),
        ]);
    }
}
