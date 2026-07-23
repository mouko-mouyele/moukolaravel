<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMileageReadingRequest;
use App\Models\MileageReading;
use App\Models\Vehicle;
use App\Services\BlockchainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MileageReadingController extends Controller
{
    public function __construct(private BlockchainService $blockchain)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $readings = MileageReading::query()
            ->with(['vehicle:id,license_plate', 'recordedBy:id,name'])
            ->when($request->vehicle_id, fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->orderByDesc('recorded_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($readings);
    }

    public function store(StoreMileageReadingRequest $request): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($request->mileage < $vehicle->current_mileage) {
            return response()->json([
                'message' => 'Fraude potentielle : le kilométrage ne peut pas diminuer.',
            ], 422);
        }

        $reading = MileageReading::create([
            ...$request->safe()->except(['certify_on_chain']),
            'recorded_by' => $request->user()->id,
            'recorded_at' => $request->recorded_at ?? now(),
        ]);

        $vehicle->update(['current_mileage' => $request->mileage]);

        $blockchainRecord = null;
        if ($request->boolean('certify_on_chain', true)) {
            $blockchainRecord = $this->blockchain->certifyMileage($reading->fresh(['vehicle']));
            if ($request->on_chain_tx_hash) {
                $this->blockchain->confirmOnChain($reading, $request->on_chain_tx_hash);
                $reading->refresh();
            }
        }

        return response()->json([
            'message' => 'Relevé kilométrique enregistré.',
            'reading' => $reading->fresh()->load(['vehicle', 'recordedBy:id,name']),
            'blockchain_record' => $blockchainRecord,
            'vehicle_uuid_hash' => $this->blockchain->vehicleUuidHash($vehicle),
        ], 201);
    }
}
