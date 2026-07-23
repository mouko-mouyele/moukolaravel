<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Vehicle;
use Tests\ApiTestCase;

class VehicleApiTest extends ApiTestCase
{
    public function test_fleet_manager_can_create_vehicle(): void
    {
        $this->actingAsRole(UserRole::FleetManager);

        $payload = [
            'vin' => 'VF1RJA00123456789',
            'license_plate' => 'AB-123-CD',
            'brand' => 'Renault',
            'model' => 'Clio',
            'year' => 2022,
            'fuel_type' => 'essence',
            'current_mileage' => 30000,
        ];

        $this->postJson('/api/v1/vehicles', $payload)
            ->assertCreated()
            ->assertJsonPath('vehicle.license_plate', 'AB-123-CD');

        $this->assertDatabaseHas('vehicles', ['vin' => 'VF1RJA00123456789']);
    }

    public function test_driver_cannot_create_vehicle(): void
    {
        $this->actingAsRole(UserRole::Driver);

        $this->postJson('/api/v1/vehicles', [
            'vin' => 'VF1RJA00123456789',
            'license_plate' => 'AB-123-CD',
            'brand' => 'Renault',
            'model' => 'Clio',
            'year' => 2022,
            'fuel_type' => 'essence',
        ])->assertForbidden();
    }

    public function test_auditor_can_list_vehicles_read_only(): void
    {
        Vehicle::factory()->count(2)->create();
        $this->actingAsRole(UserRole::Auditor);

        $this->getJson('/api/v1/vehicles')
            ->assertOk();
    }
}
