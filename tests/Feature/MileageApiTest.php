<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Vehicle;
use Tests\ApiTestCase;

class MileageApiTest extends ApiTestCase
{
    public function test_driver_can_record_mileage(): void
    {
        $vehicle = Vehicle::factory()->create(['current_mileage' => 40000]);
        $this->actingAsRole(UserRole::Driver);

        $this->postJson('/api/v1/mileage-readings', [
            'vehicle_id' => $vehicle->id,
            'mileage' => 40500,
            'certify_on_chain' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('mileage_readings', [
            'vehicle_id' => $vehicle->id,
            'mileage' => 40500,
        ]);

        $vehicle->refresh();
        $this->assertSame(40500, $vehicle->current_mileage);
    }

    public function test_mileage_rollback_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create(['current_mileage' => 50000]);
        $this->actingAsRole(UserRole::Driver);

        $this->postJson('/api/v1/mileage-readings', [
            'vehicle_id' => $vehicle->id,
            'mileage' => 49000,
        ])->assertStatus(422);
    }
}
