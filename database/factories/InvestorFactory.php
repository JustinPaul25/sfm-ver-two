<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Investor>
 */
class InvestorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyTypes = ['Aquaculture Farm', 'Fish Farm', 'Tilapia Farm', 'Aqua Ventures', 'Fish Co.', 'Aqua Solutions'];
        $companyName = fake()->randomElement($companyTypes) . ' ' . fake()->company();
        
        return [
            'name' => $companyName,
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
        ];
    }
} 