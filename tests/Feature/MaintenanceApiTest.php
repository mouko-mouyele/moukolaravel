<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Vehicle;
use Tests\ApiTestCase;

class MaintenanceApiTest extends ApiTestCase
{
    public function test_mechanic_can_register_maintenance(): void
    {
        $vehicle = Vehicle::factory()->create(['current_mileage' => 60000]);
        $this->actingAsRole(UserRole::Mechanic);

        $this->postJson('/api/v1/maintenances', [
            'vehicle_id' => $vehicle->id,
            'intervention_type' => 'Vidange',
            'mileage_at_service' => 60000,
            'service_date' => now()->format('Y-m-d'),
            'cost' => 89.90,
            'parts_changed' => [['name' => 'Filtre huile', 'quantity' => 1]],
            'certify_on_chain' => true,
        ])->assertCreated()
            ->assertJsonPath('maintenance.intervention_type', 'Vidange');

        $this->assertDatabaseHas('maintenances', [
            'vehicle_id' => $vehicle->id,
            'certified_on_chain' => true,
        ]);
    }
}
