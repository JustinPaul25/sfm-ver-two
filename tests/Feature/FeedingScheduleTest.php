<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Investor;
use App\Models\Cage;
use App\Models\FeedType;
use App\Models\Sampling;
use App\Models\Sample;
use App\Models\CageFeedingSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedingScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_generate_schedule_uses_weight_based_calculation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test data with weight measurements
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
            'number_of_fingerlings' => 5000,
        ]);

        // Create sampling with weight data
        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'date_sampling' => now()->subDays(30),
            'mortality' => 100,
        ]);

        // Create samples with known weights
        Sample::factory()->count(10)->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 150, // 150g average
        ]);

        // Generate feeding schedule
        $response = $this->postJson('/cages/feeding-schedules/auto-generate', [
            'cage_ids' => [$cage->id],
            'overwrite_existing' => true,
        ]);

        $response->assertStatus(200);
        
        // Verify schedule was created with weight-based calculation
        $schedule = CageFeedingSchedule::where('cage_id', $cage->id)
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($schedule);
        
        // Check that notes indicate weight-based calculation
        $this->assertStringContainsString('weight-based', $schedule->notes);
        $this->assertStringContainsString('150', $schedule->notes); // Should contain the average weight
        $this->assertStringContainsString('3% of body weight', $schedule->notes);
        
        // Verify the calculation: (5000 - 100) * 150g * 3% / 1000 = 22.05 kg
        // Note: Due to rounding per feeding time, sum may be slightly off (22.04 instead of 22.05)
        $expectedAmount = (4900 * 150 * 0.03) / 1000;
        $this->assertGreaterThanOrEqual($expectedAmount - 0.02, $schedule->total_daily_amount);
        $this->assertLessThanOrEqual($expectedAmount + 0.02, $schedule->total_daily_amount);
    }

    public function test_auto_generate_schedule_falls_back_to_age_based_calculation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test data without weight measurements
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
            'number_of_fingerlings' => 10000, // Use larger number for more precision
        ]);

        // Create sampling with 75 day old fish (middle of 60-90 range)
        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'date_sampling' => now()->subDays(75),
        ]);

        // Generate feeding schedule
        $response = $this->postJson('/cages/feeding-schedules/auto-generate', [
            'cage_ids' => [$cage->id],
            'overwrite_existing' => true,
        ]);

        $response->assertStatus(200);
        
        // Verify schedule was created with age-based calculation
        $schedule = CageFeedingSchedule::where('cage_id', $cage->id)
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($schedule);
        
        // Check that notes indicate age-based calculation
        $this->assertStringContainsString('age-based', $schedule->notes);
        
        // For 75 day old fish: 1.0g per fish = 10000 * 1.0 / 1000 = 10 kg
        $expectedAmount = 10.0;
        $this->assertEquals($expectedAmount, $schedule->total_daily_amount);
    }

    public function test_feeding_schedule_calculation_uses_present_stocks()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
            'number_of_fingerlings' => 10000,
        ]);

        // Create sampling with mortality
        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'date_sampling' => now()->subDays(30),
            'mortality' => 1500,
        ]);

        // Create samples with known weights
        Sample::factory()->count(10)->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 200,
        ]);

        // Generate feeding schedule
        $response = $this->postJson('/cages/feeding-schedules/auto-generate', [
            'cage_ids' => [$cage->id],
            'overwrite_existing' => true,
        ]);

        $response->assertStatus(200);
        
        $schedule = CageFeedingSchedule::where('cage_id', $cage->id)
            ->where('is_active', true)
            ->first();

        // Verify it uses present stocks (10000 - 1500 = 8500), not total fingerlings
        // Calculation: 8500 * 200g * 3% / 1000 = 51 kg
        $expectedAmount = round((8500 * 200 * 0.03) / 1000, 2);
        $this->assertEquals($expectedAmount, $schedule->total_daily_amount);
    }

    public function test_feeding_frequency_based_on_age()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
            'number_of_fingerlings' => 5000,
        ]);

        // Test 10 day old fish - should be four times daily
        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'date_sampling' => now()->subDays(10),
        ]);

        Sample::factory()->count(10)->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 50,
        ]);

        $response = $this->postJson('/cages/feeding-schedules/auto-generate', [
            'cage_ids' => [$cage->id],
            'overwrite_existing' => true,
        ]);

        $response->assertStatus(200);
        
        $schedule = CageFeedingSchedule::where('cage_id', $cage->id)
            ->where('is_active', true)
            ->first();

        $this->assertEquals('four_times_daily', $schedule->frequency);
        $this->assertNotNull($schedule->feeding_time_4); // Should have 4 feeding times
    }
}

