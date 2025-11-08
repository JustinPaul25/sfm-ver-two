<?php

namespace Database\Factories;

use App\Models\Investor;
use App\Models\FeedType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cage>
 */
class CageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number_of_fingerlings' => fake()->numberBetween(3000, 8000),
            'feed_types_id' => FeedType::factory(),
            'investor_id' => Investor::factory(),
        ];
    }
} 