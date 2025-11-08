<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Investor;
use App\Models\Cage;
use App\Models\FeedType;
use App\Models\Sampling;
use App\Models\Sample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_dashboard_displays_analytics_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test data
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
        ]);

        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'date_sampling' => now(),
        ]);

        $sample = Sample::factory()->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 100,
        ]);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Check that analytics data is present
        $props = $response->viewData('page')['props'] ?? [];
        $this->assertArrayHasKey('analytics', $props);
        $this->assertArrayHasKey('summary', $props['analytics']);
        $this->assertArrayHasKey('weight_stats', $props['analytics']);
        $this->assertArrayHasKey('top_investors', $props['analytics']);
        $this->assertArrayHasKey('feed_type_usage', $props['analytics']);
        $this->assertArrayHasKey('growth_metrics', $props['analytics']);
        $this->assertArrayHasKey('date_range', $props['analytics']);
    }

    public function test_dashboard_filters_by_period()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test data for different periods
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
        ]);

        // Create sampling this week
        $samplingThisWeek = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'date_sampling' => now()->subDays(3),
        ]);

        Sample::factory()->create([
            'investor_id' => $investor->id,
            'sampling_id' => $samplingThisWeek->id,
            'weight' => 100,
        ]);

        // Create sampling last month
        $samplingLastMonth = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'date_sampling' => now()->subMonth()->subDays(10),
        ]);

        Sample::factory()->create([
            'investor_id' => $investor->id,
            'sampling_id' => $samplingLastMonth->id,
            'weight' => 90,
        ]);

        // Test with different periods
        $response = $this->get('/dashboard?period=week');
        $response->assertStatus(200);
        $props = $response->viewData('page')['props'];
        $this->assertEquals(1, $props['analytics']['summary']['samplings_in_period']);

        $response = $this->get('/dashboard?period=month');
        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_analytics_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test data
        $investor1 = Investor::factory()->create(['name' => 'Investor 1']);
        $investor2 = Investor::factory()->create(['name' => 'Investor 2']);
        $feedType = FeedType::factory()->create();
        
        $cage1 = Cage::factory()->create([
            'investor_id' => $investor1->id,
            'feed_types_id' => $feedType->id,
        ]);
        
        $cage2 = Cage::factory()->create([
            'investor_id' => $investor2->id,
            'feed_types_id' => $feedType->id,
        ]);

        // Create samplings with samples
        $sampling1 = Sampling::factory()->create([
            'investor_id' => $investor1->id,
            'cage_no' => $cage1->id,
            'date_sampling' => now(),
        ]);

        Sample::factory()->count(3)->create([
            'investor_id' => $investor1->id,
            'sampling_id' => $sampling1->id,
            'weight' => 100,
        ]);

        $sampling2 = Sampling::factory()->create([
            'investor_id' => $investor2->id,
            'cage_no' => $cage2->id,
            'date_sampling' => now(),
        ]);

        Sample::factory()->count(2)->create([
            'investor_id' => $investor2->id,
            'sampling_id' => $sampling2->id,
            'weight' => 150,
        ]);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $props = $response->viewData('page')['props'];
        $analytics = $props['analytics'];

        // Verify counts
        $this->assertEquals(2, $analytics['summary']['total_investors']);
        $this->assertEquals(2, $analytics['summary']['total_cages']);
        $this->assertEquals(2, $analytics['summary']['samplings_in_period']);

        // Verify weight stats
        $this->assertEquals(5, $analytics['weight_stats']['total_samples']);
        $this->assertEquals(120, $analytics['weight_stats']['avg_weight']); // (100+100+100+150+150)/5 = 120

        // Verify top investors
        $this->assertNotEmpty($analytics['top_investors']);
        
        // Verify feed type usage
        $this->assertNotEmpty($analytics['feed_type_usage']);
        
        // Verify sampling trends
        $this->assertNotEmpty($analytics['sampling_trends']);
        
        // Verify growth metrics exist
        $this->assertArrayHasKey('sampling_growth', $analytics['growth_metrics']);
        $this->assertArrayHasKey('weight_growth', $analytics['growth_metrics']);
        
        // Verify date range
        $this->assertArrayHasKey('start', $analytics['date_range']);
        $this->assertArrayHasKey('end', $analytics['date_range']);
        $this->assertArrayHasKey('period', $analytics['date_range']);
    }
}
