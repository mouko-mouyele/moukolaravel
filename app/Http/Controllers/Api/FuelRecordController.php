<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFuelRecordRequest;
use App\Models\FuelRecord;
use App\Models\Vehicle;
use App\Services\ConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FuelRecordController extends Controller
{
    public function __construct(private ConsumptionService $consumptionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $records = FuelRecord::query()
            ->with(['vehicle:id,license_plate', 'recordedBy:id,name'])
            ->when($request->vehicle_id, fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->orderByDesc('filled_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($records);
    }

    public function store(StoreFuelRecordRequest $request): JsonResponse
    {
        $record = FuelRecord::create([
            ...$request->validated(),
            'recorded_by' => $request->user()->id,
            'filled_at' => $request->filled_at ?? now(),
        ]);

        $consumption = $this->consumptionService->calculateForRecord($record);
        if ($consumption !== null) {
            $record->update(['consumption_per_100km' => $consumption]);
        }

        $vehicle = Vehicle::find($request->vehicle_id);
        if ($vehicle && $request->mileage_at_fill > $vehicle->current_mileage) {
            $vehicle->update(['current_mileage' => $request->mileage_at_fill]);
        }

        return response()->json([
            'message' => 'Plein enregistré.',
            'record' => $record->fresh()->load(['vehicle', 'recordedBy:id,name']),
            'average_consumption' => $vehicle
                ? $this->consumptionService->averageForVehicle($vehicle)
                : null,
        ], 201);
    }

    public function vehicleStats(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'vehicle_id' => $vehicle->id,
            'average_consumption' => $this->consumptionService->averageForVehicle($vehicle),
            'records_count' => $vehicle->fuelRecords()->count(),
            'recent_records' => $vehicle->fuelRecords()->orderByDesc('filled_at')->limit(5)->get(),
        ]);
    }
}
