<?php

namespace Database\Factories;

use App\Models\Investor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sampling>
 */
class SamplingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'investor_id' => Investor::factory(),
            'date_sampling' => fake()->dateTimeBetween('-6 months', 'now'),
            'doc' => fake()->numberBetween(1, 200),
            'cage_no' => fake()->numberBetween(1, 20),
        ];
    }
} 