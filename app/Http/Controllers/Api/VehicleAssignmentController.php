<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssignmentStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\BlockchainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleAssignmentController extends Controller
{
    public function __construct(private BlockchainService $blockchain)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $assignments = VehicleAssignment::query()
            ->with(['vehicle', 'driver:id,name,email', 'assignedBy:id,name'])
            ->when($request->vehicle_id, fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->when($request->driver_id, fn ($q) => $q->where('driver_id', $request->driver_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($assignments);
    }

    public function store(StoreAssignmentRequest $request, Vehicle $vehicle): JsonResponse
    {
        if ($vehicle->status === VehicleStatus::Sold) {
            return response()->json(['message' => 'Ce véhicule a été vendu.'], 422);
        }

        $hasActive = $vehicle->assignments()->where('status', AssignmentStatus::Active)->exists();
        if ($hasActive) {
            return response()->json(['message' => 'Ce véhicule a déjà une affectation active.'], 422);
        }

        $assignment = VehicleAssignment::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $request->driver_id,
            'assigned_by' => $request->user()->id,
            'status' => AssignmentStatus::Active,
            'start_mileage' => $request->start_mileage ?? $vehicle->current_mileage,
            'started_at' => now(),
            'notes' => $request->notes,
        ]);

        $vehicle->update(['status' => VehicleStatus::InMission]);

        $record = $this->blockchain->registerRecord('assignment', [
            'vehicle_uuid' => $vehicle->uuid,
            'driver_id' => $assignment->driver_id,
            'started_at' => $assignment->started_at->toIso8601String(),
        ], $vehicle, $assignment);

        if ($record->tx_hash) {
            $assignment->update(['blockchain_tx_hash' => $record->tx_hash]);
        }

        return response()->json([
            'message' => 'Véhicule affecté au chauffeur.',
            'assignment' => $assignment->load(['vehicle', 'driver:id,name', 'assignedBy:id,name']),
        ], 201);
    }

    public function complete(Request $request, VehicleAssignment $assignment): JsonResponse
    {
        if ($assignment->status !== AssignmentStatus::Active) {
            return response()->json(['message' => 'Cette affectation n\'est pas active.'], 422);
        }

        $request->validate([
            'end_mileage' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $assignment->update([
            'status' => AssignmentStatus::Completed,
            'end_mileage' => $request->end_mileage,
            'ended_at' => now(),
            'notes' => $request->notes ?? $assignment->notes,
        ]);

        $vehicle = $assignment->vehicle;
        $vehicle->update([
            'status' => VehicleStatus::Available,
            'current_mileage' => max($vehicle->current_mileage, $request->end_mileage),
        ]);

        return response()->json([
            'message' => 'Prise en charge terminée.',
            'assignment' => $assignment->fresh()->load(['vehicle', 'driver:id,name']),
        ]);
    }
}
