<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Investor;
use App\Models\Cage;
use App\Models\FeedType;
use App\Models\Sampling;
use App\Models\Sample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set API key for testing
        $this->apiKey = 'test-api-key';
        config(['services.api_key' => $this->apiKey]);
    }

    public function test_cages_endpoint_requires_api_key()
    {
        $response = $this->getJson('/api/cages');
        $response->assertStatus(422)
                 ->assertJson(['message' => 'Key is required or the key is invalid']);
    }

    public function test_cages_endpoint_with_invalid_key()
    {
        $response = $this->getJson('/api/cages?key=invalid-key');
        $response->assertStatus(422)
                 ->assertJson(['message' => 'Key is required or the key is invalid']);
    }

    public function test_cages_endpoint_returns_cages()
    {
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
        ]);

        $response = $this->getJson("/api/cages?key={$this->apiKey}");
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'investor_id',
                             'feed_types_id',
                             'samplings',
                         ]
                     ]
                 ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_weight_endpoint_requires_valid_data()
    {
        $response = $this->postJson("/api/weight?key={$this->apiKey}", []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['height', 'width', 'doc']);
    }

    public function test_weight_endpoint_calculates_weight()
    {
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
            'doc' => 'DOC-WEIGHT-TEST-1',
        ]);

        // Create sample with weight 0
        $sample = Sample::factory()->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 0,
            'sample_no' => 1,
        ]);

        $response = $this->postJson("/api/weight?key={$this->apiKey}", [
            'height' => 10,
            'width' => 5,
            'doc' => $sampling->doc,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'weight',
                         'sample_no',
                         'abw',
                         'total_weight',
                         'remaining_samples'
                     ]
                 ]);

        // Check that weight was calculated and saved
        $sample->refresh();
        $this->assertGreaterThan(0, $sample->weight);
        $this->assertEqualsWithDelta(10.0, (float) $sample->length, 0.01);
        $this->assertEqualsWithDelta(5.0, (float) $sample->width, 0.01);
    }

    public function test_weight_endpoint_converts_inches_to_cm_and_orders_length_width()
    {
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
        ]);

        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'doc' => 'DOC-INCH-TEST',
        ]);

        $sample = Sample::factory()->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 0,
            'sample_no' => 1,
        ]);

        // Bbox-style: smaller value first (would be "height"), larger second ("width") — server assigns long→length, short→width.
        $response = $this->postJson("/api/weight?key={$this->apiKey}", [
            'height' => 2.27,
            'width' => 8.23,
            'doc' => $sampling->doc,
            'unit' => 'in',
        ]);

        $response->assertStatus(200);
        $sample->refresh();
        $this->assertEqualsWithDelta(20.9, (float) $sample->length, 0.05);
        $this->assertEqualsWithDelta(5.77, (float) $sample->width, 0.05);
    }

    public function test_weight_endpoint_rejects_when_all_samples_filled()
    {
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
        ]);

        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
            'doc' => 'DOC-WEIGHT-TEST-2',
        ]);

        // All five slots filled — API must not auto-create empty rows to accept more.
        for ($i = 1; $i <= 5; $i++) {
            Sample::factory()->create([
                'investor_id' => $investor->id,
                'sampling_id' => $sampling->id,
                'weight' => 100,
                'sample_no' => (string) $i,
            ]);
        }

        $response = $this->postJson("/api/weight?key={$this->apiKey}", [
            'height' => 10,
            'width' => 5,
            'doc' => $sampling->doc,
        ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'All data is filled in this sampling.']);
    }

    public function test_calculate_samplings_endpoint_requires_sampling_id()
    {
        $response = $this->postJson("/api/sampling/calculate?key={$this->apiKey}", []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sampling_id']);
    }

    public function test_calculate_samplings_endpoint_returns_next_sample()
    {
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
        ]);

        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
        ]);

        $sample = Sample::factory()->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 0,
            'sample_no' => 1,
        ]);

        $response = $this->postJson("/api/sampling/calculate?key={$this->apiKey}", [
            'sampling_id' => $sampling->id,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'sampling_id',
                         'current_sample',
                         'progress',
                         'sampling_info'
                     ]
                 ]);

        $this->assertEquals($sample->id, $response->json('data.current_sample.id'));
    }

    public function test_get_sampling_endpoint_returns_sampling_details()
    {
        $investor = Investor::factory()->create();
        $feedType = FeedType::factory()->create();
        $cage = Cage::factory()->create([
            'investor_id' => $investor->id,
            'feed_types_id' => $feedType->id,
        ]);

        $sampling = Sampling::factory()->create([
            'investor_id' => $investor->id,
            'cage_no' => $cage->id,
        ]);

        $sample = Sample::factory()->create([
            'investor_id' => $investor->id,
            'sampling_id' => $sampling->id,
            'weight' => 100,
        ]);

        $response = $this->getJson("/api/sampling/{$sampling->id}?key={$this->apiKey}");
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'sampling',
                         'statistics'
                     ]
                 ]);

        $this->assertEquals($sampling->id, $response->json('data.sampling.id'));
        $this->assertEquals(100, $response->json('data.statistics.average_weight'));
    }
}

