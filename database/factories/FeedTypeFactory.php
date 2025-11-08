<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedType>
 */
class FeedTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feedTypes = [
            'Starter Feed (0.5mm)',
            'Starter Feed (1.0mm)',
            'Grower Feed (2.0mm)',
            'Grower Feed (3.0mm)',
            'Finisher Feed (4.0mm)',
            'Finisher Feed (5.0mm)',
            'Premium Grower Feed',
            'High Protein Feed',
            'Floating Feed',
            'Sinking Feed'
        ];
        
        return [
            'feed_type' => fake()->randomElement($feedTypes) . ' ' . fake()->randomNumber(3),
            'brand' => fake()->company(),
        ];
    }
} 