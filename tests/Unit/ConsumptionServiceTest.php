<?php

namespace Tests\Unit;

use App\Models\FuelRecord;
use App\Models\Vehicle;
use App\Services\BlockchainService;
use App\Services\ConsumptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_consumption_between_two_fills(): void
    {
        $vehicle = Vehicle::factory()->create();
        $service = new ConsumptionService;

        FuelRecord::create([
            'vehicle_id' => $vehicle->id,
            'recorded_by' => $vehicle->registered_by,
            'mileage_at_fill' => 10000,
            'liters' => 40,
            'filled_at' => now()->subDays(7),
        ]);

        $second = FuelRecord::create([
            'vehicle_id' => $vehicle->id,
            'recorded_by' => $vehicle->registered_by,
            'mileage_at_fill' => 10500,
            'liters' => 35,
            'filled_at' => now(),
        ]);

        $consumption = $service->calculateForRecord($second);

        $this->assertSame(7.0, $consumption);
    }

    public function test_blockchain_service_hashes_payload_deterministically(): void
    {
        $service = new BlockchainService;
        $payload = ['b' => 2, 'a' => 1];

        $hash1 = $service->hashPayload($payload);
        $hash2 = $service->hashPayload(['a' => 1, 'b' => 2]);

        $this->assertSame($hash1, $hash2);
        $this->assertSame(64, strlen($hash1));
    }
}
