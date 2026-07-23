<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Services\BlockchainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function __construct(private BlockchainService $blockchain)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $maintenances = Maintenance::query()
            ->with(['vehicle:id,license_plate,uuid', 'mechanic:id,name'])
            ->when($request->vehicle_id, fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->orderByDesc('service_date')
            ->paginate($request->integer('per_page', 15));

        return response()->json($maintenances);
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($request->mileage_at_service < $vehicle->current_mileage) {
            return response()->json([
                'message' => 'Le kilométrage de service ne peut pas être inférieur au compteur actuel.',
            ], 422);
        }

        $maintenance = Maintenance::create([
            ...$request->safe()->except(['certify_on_chain']),
            'mechanic_id' => $request->user()->id,
        ]);

        $vehicle->update(['current_mileage' => $request->mileage_at_service]);

        $blockchainRecord = null;
        if ($request->boolean('certify_on_chain', true)) {
            $blockchainRecord = $this->blockchain->certifyMaintenance($maintenance->fresh(['vehicle']));
            if ($request->on_chain_tx_hash) {
                $this->blockchain->confirmOnChain($maintenance, $request->on_chain_tx_hash);
                $maintenance->refresh();
            }
        }

        return response()->json([
            'message' => 'Maintenance enregistrée.',
            'maintenance' => $maintenance->fresh()->load(['vehicle', 'mechanic:id,name']),
            'blockchain_record' => $blockchainRecord,
            'vehicle_uuid_hash' => $this->blockchain->vehicleUuidHash($vehicle),
            'parts_hash' => hash('sha256', json_encode($maintenance->parts_changed ?? [])),
        ], 201);
    }

    public function show(Maintenance $maintenance): JsonResponse
    {
        return response()->json([
            'maintenance' => $maintenance->load(['vehicle', 'mechanic:id,name']),
        ]);
    }
}
