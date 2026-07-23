<?php

namespace App\Http\Controllers\Api;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Services\AlertEngineService;
use App\Services\BlockchainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(
        private BlockchainService $blockchain,
        private AlertEngineService $alertEngine
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::query()
            ->with('registeredBy:id,name')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('license_plate', 'like', "%{$request->search}%")
                    ->orWhere('vin', 'like', "%{$request->search}%")
                    ->orWhere('brand', 'like', "%{$request->search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($vehicles);
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create([
            ...$request->validated(),
            'registered_by' => $request->user()->id,
        ]);

        $record = $this->blockchain->registerRecord('vehicle_registration', [
            'vehicle_uuid' => $vehicle->uuid,
            'vin_hash' => hash('sha256', $vehicle->vin),
            'license_plate' => $vehicle->license_plate,
        ], $vehicle);

        if ($request->on_chain_tx_hash) {
            $this->blockchain->confirmTransaction($record, $request->on_chain_tx_hash);
        }

        $this->alertEngine->generateForVehicle($vehicle);

        return response()->json([
            'message' => 'Véhicule enregistré.',
            'vehicle' => $vehicle->load('registeredBy:id,name'),
            'blockchain_record' => $record->fresh(),
            'vehicle_uuid_hash' => $this->blockchain->vehicleUuidHash($vehicle),
        ], 201);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'vehicle' => $vehicle->load([
                'registeredBy:id,name',
                'documents',
                'alerts' => fn ($q) => $q->where('is_resolved', false)->latest(),
            ]),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());
        $this->alertEngine->generateForVehicle($vehicle->fresh());

        return response()->json([
            'message' => 'Véhicule mis à jour.',
            'vehicle' => $vehicle->fresh(),
        ]);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->update(['status' => VehicleStatus::OutOfService]);
        $vehicle->delete();

        return response()->json(['message' => 'Véhicule archivé.']);
    }
}
