<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Tests\ApiTestCase;

class DriverApiTest extends ApiTestCase
{
    public function test_driver_can_access_mobile_dashboard(): void
    {
        $driver = $this->actingAsRole(\App\Enums\UserRole::Driver);

        $this->getJson('/api/v1/driver/dashboard')
            ->assertOk()
            ->assertJsonStructure(['driver', 'active_assignment', 'has_active_mission']);
    }

    public function test_driver_can_declare_pickup_and_complete_mission(): void
    {
        $driver = User::factory()->role(\App\Enums\UserRole::Driver)->create();
        $vehicle = Vehicle::factory()->create(['current_mileage' => 50000]);

        $assignment = VehicleAssignment::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'assigned_by' => User::factory()->role(\App\Enums\UserRole::FleetManager)->create()->id,
            'status' => AssignmentStatus::Active,
            'started_at' => now(),
        ]);

        $this->actingAs($driver, 'sanctum');

        $this->postJson("/api/v1/driver/assignments/{$assignment->id}/pickup", [
            'start_mileage' => 50000,
        ])->assertOk();

        $this->postJson("/api/v1/driver/assignments/{$assignment->id}/complete", [
            'end_mileage' => 50150,
        ])->assertOk();

        $this->assertDatabaseHas('vehicle_assignments', [
            'id' => $assignment->id,
            'status' => 'completed',
            'end_mileage' => 50150,
        ]);
    }
}
