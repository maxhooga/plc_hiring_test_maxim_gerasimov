<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VehicleTrendingTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_vehicle_data(): void
    {
        $vehicle = Vehicle::create([
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'price' => 18500,
        ]);

        $response = $this->getJson("/api/vehicles/{$vehicle->id}");

        $response
            ->assertOk()
            ->assertJson([
                'id' => $vehicle->id,
                'make' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2022,
                'price' => 18500,
            ]);
    }

    public function test_trending_returns_top_ten_most_viewed_vehicles_in_order(): void
    {
        $popular = $this->createVehicle('Toyota', 'Corolla');
        $mid = $this->createVehicle('BMW', '320i');
        $quiet = $this->createVehicle('Audi', 'A3');

        $this->recordViews($popular, 5);
        $this->recordViews($mid, 3);
        $this->recordViews($quiet, 1);

        $response = $this->getJson('/api/vehicles/trending');

        $response
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => ['id', 'make', 'model', 'year', 'price', 'view_count'],
            ])
            ->assertJsonPath('0.id', $popular->id)
            ->assertJsonPath('0.view_count', 5)
            ->assertJsonPath('1.id', $mid->id)
            ->assertJsonPath('1.view_count', 3)
            ->assertJsonPath('2.id', $quiet->id)
            ->assertJsonPath('2.view_count', 1);
    }

    public function test_trending_breaks_ties_by_lower_vehicle_id(): void
    {
        $lowerId = $this->createVehicle('Skoda', 'Octavia');
        $higherId = $this->createVehicle('Volkswagen', 'Golf');

        $this->recordViews($lowerId, 2);
        $this->recordViews($higherId, 2);

        $response = $this->getJson('/api/vehicles/trending');

        $response
            ->assertOk()
            ->assertJsonPath('0.id', $lowerId->id)
            ->assertJsonPath('0.view_count', 2)
            ->assertJsonPath('1.id', $higherId->id)
            ->assertJsonPath('1.view_count', 2);
    }

    public function test_trending_returns_at_most_ten_vehicles(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $vehicle = $this->createVehicle('Make', "Model {$i}");
            $this->recordViews($vehicle, 12 - $i);
        }

        $response = $this->getJson('/api/vehicles/trending');

        $response->assertOk()->assertJsonCount(10);

        $returnedViewCounts = collect($response->json())->pluck('view_count')->all();
        $this->assertSame([12, 11, 10, 9, 8, 7, 6, 5, 4, 3], $returnedViewCounts);
    }

    public function test_trending_only_counts_views_from_the_last_twenty_four_hours(): void
    {
        $recent = $this->createVehicle('Hyundai', 'Tucson');
        $stale = $this->createVehicle('Kia', 'Sportage');

        $this->recordViews($recent, 2);

        DB::table('vehicle_views')->insert([
            'vehicle_id' => $stale->id,
            'viewed_at' => now()->subHours(25),
        ]);

        $response = $this->getJson('/api/vehicles/trending');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $recent->id)
            ->assertJsonPath('0.view_count', 2);
    }

    private function createVehicle(string $make, string $model): Vehicle
    {
        return Vehicle::create([
            'make' => $make,
            'model' => $model,
            'year' => 2021,
            'price' => 20000,
        ]);
    }

    private function recordViews(Vehicle $vehicle, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->getJson("/api/vehicles/{$vehicle->id}")->assertOk();
        }
    }
}
