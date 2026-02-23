<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'date' => fake()->dateTimeBetween('now', '+6 months'),
            'location' => fake()->city() . ', ' . fake()->country(),
            'created_by' => User::factory()->organizer(),
        ];
    }
}
