<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BlockchainService;
use App\Services\SaleSignatureService;
use Tests\ApiTestCase;

class SaleApiTest extends ApiTestCase
{
    public function test_fleet_manager_can_prepare_and_initiate_sale(): void
    {
        $manager = User::factory()->role(UserRole::FleetManager)->create([
            'wallet_address' => '0x00000000000000000000000000000000000000aa',
        ]);
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($manager, 'sanctum');

        $prepare = $this->postJson('/api/v1/blockchain/sales/prepare', [
            'vehicle_id' => $vehicle->id,
            'buyer_wallet' => '0x0000000000000000000000000000000000000002',
            'sale_price' => 12000,
        ])->assertOk()
            ->assertJsonStructure(['admin_message', 'data_hash', 'payload']);

        $this->postJson('/api/v1/blockchain/sales/initiate', [
            'vehicle_id' => $vehicle->id,
            'buyer_wallet' => '0x0000000000000000000000000000000000000002',
            'sale_price' => 12000,
            'admin_signature' => '0x'.str_repeat('ab', 32),
        ])->assertCreated();

        $this->assertDatabaseHas('pending_signatures', [
            'vehicle_id' => $vehicle->id,
            'buyer_wallet' => '0x0000000000000000000000000000000000000002',
            'status' => 'pending',
        ]);
    }

    public function test_buyer_can_sign_sale_with_metamask_signature(): void
    {
        $blockchain = app(BlockchainService::class);
        $saleService = app(SaleSignatureService::class);

        $manager = User::factory()->role(UserRole::FleetManager)->create();
        $auditor = User::factory()->role(UserRole::Auditor)->create([
            'wallet_address' => '0x0000000000000000000000000000000000000002',
        ]);
        $vehicle = Vehicle::factory()->create();

        $payload = $saleService->buildPayload($vehicle, '0x0000000000000000000000000000000000000002', 15000, $manager->id);
        $dataHash = $blockchain->hashPayload($payload);

        $pending = \App\Models\PendingSignature::create([
            'operation_type' => 'vehicle_sale',
            'vehicle_id' => $vehicle->id,
            'payload' => $payload,
            'data_hash' => $dataHash,
            'initiated_by' => $manager->id,
            'admin_signature' => '0x'.str_repeat('aa', 32),
            'buyer_wallet' => '0x0000000000000000000000000000000000000002',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $buyerMessage = $saleService->buildBuyerMessage($pending);

        $this->actingAs($auditor, 'sanctum');

        $this->postJson("/api/v1/blockchain/sales/{$pending->id}/sign", [
            'buyer_signature' => '0x'.str_repeat('cd', 32),
        ])->assertOk();

        $this->assertDatabaseHas('pending_signatures', [
            'id' => $pending->id,
            'status' => 'completed',
        ]);

        $vehicle->refresh();
        $this->assertSame('sold', $vehicle->status->value);
    }
}
