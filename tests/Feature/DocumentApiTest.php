<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ApiTestCase;

class DocumentApiTest extends ApiTestCase
{
    public function test_fleet_manager_can_upload_vehicle_document(): void
    {
        Storage::fake('local');
        $vehicle = Vehicle::factory()->create();
        $this->actingAsRole(UserRole::FleetManager);

        $file = UploadedFile::fake()->create('assurance.pdf', 100, 'application/pdf');

        $this->postJson("/api/v1/vehicles/{$vehicle->id}/documents", [
            'type' => 'insurance',
            'title' => 'Assurance 2026',
            'is_public' => false,
            'file' => $file,
        ])->assertCreated()
            ->assertJsonPath('document.title', 'Assurance 2026');

        $this->assertDatabaseHas('documents', [
            'vehicle_id' => $vehicle->id,
            'title' => 'Assurance 2026',
        ]);
    }

    public function test_auditor_can_list_documents_read_only(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAsRole(UserRole::Auditor);

        $this->getJson("/api/v1/vehicles/{$vehicle->id}/documents")
            ->assertOk();
    }
}
