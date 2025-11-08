<?php

namespace Database\Factories;

use App\Models\Investor;
use App\Models\Sampling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sample>
 */
class SampleFactory extends Factory
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
            'sampling_id' => Sampling::factory(),
            'sample_no' => fake()->numberBetween(1, 50),
            'weight' => fake()->numberBetween(50, 500), // Weight in grams
        ];
    }
} 