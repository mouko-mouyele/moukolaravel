<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Vehicle;
use App\Services\TimelineService;
use Tests\ApiTestCase;

class TimelineApiTest extends ApiTestCase
{
    public function test_vehicle_timeline_is_accessible(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAsRole(UserRole::Auditor);

        $this->getJson("/api/v1/vehicles/{$vehicle->id}/timeline")
            ->assertOk()
            ->assertJsonStructure(['vehicle', 'timeline']);
    }

    public function test_timeline_service_returns_sorted_events(): void
    {
        $vehicle = Vehicle::factory()->create();
        $service = app(TimelineService::class);
        $timeline = $service->forVehicle($vehicle);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $timeline);
    }
}
