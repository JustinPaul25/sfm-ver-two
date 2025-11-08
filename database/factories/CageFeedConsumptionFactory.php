<?php

namespace Database\Factories;

use App\Models\Cage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CageFeedConsumption>
 */
class CageFeedConsumptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cage_id' => Cage::factory(),
            'day_number' => $this->faker->numberBetween(1, 365),
            'feed_amount' => $this->faker->randomFloat(2, 0.5, 10.0),
            'consumption_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
} 