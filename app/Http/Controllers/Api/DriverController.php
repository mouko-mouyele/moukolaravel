<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssignmentStatus;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\VehicleAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $driver = $request->user();

        $activeAssignment = VehicleAssignment::query()
            ->with(['vehicle:id,uuid,license_plate,brand,model,current_mileage,fuel_type,status'])
            ->where('driver_id', $driver->id)
            ->where('status', AssignmentStatus::Active)
            ->latest()
            ->first();

        $recentMissions = VehicleAssignment::query()
            ->with(['vehicle:id,license_plate,brand,model'])
            ->where('driver_id', $driver->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'driver' => $driver->only(['id', 'name', 'wallet_address']),
            'active_assignment' => $activeAssignment,
            'recent_missions' => $recentMissions,
            'has_active_mission' => $activeAssignment !== null,
        ]);
    }

    public function declarePickup(Request $request, VehicleAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json(['message' => 'Cette mission ne vous est pas assignée.'], 403);
        }

        if ($assignment->status !== AssignmentStatus::Active) {
            return response()->json(['message' => 'Mission déjà terminée ou annulée.'], 422);
        }

        $request->validate([
            'start_mileage' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $vehicle = $assignment->vehicle;
        $startMileage = $request->start_mileage ?? $vehicle->current_mileage;

        $assignment->update([
            'start_mileage' => $startMileage,
            'started_at' => $assignment->started_at ?? now(),
            'notes' => $request->notes ?? $assignment->notes,
        ]);

        $vehicle->update(['status' => VehicleStatus::InMission]);

        return response()->json([
            'message' => 'Prise en charge déclarée.',
            'assignment' => $assignment->fresh()->load('vehicle'),
        ]);
    }

    public function completeMission(Request $request, VehicleAssignment $assignment): JsonResponse
    {
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json(['message' => 'Cette mission ne vous est pas assignée.'], 403);
        }

        if ($assignment->status !== AssignmentStatus::Active) {
            return response()->json(['message' => 'Cette mission n\'est pas active.'], 422);
        }

        $request->validate([
            'end_mileage' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $vehicle = $assignment->vehicle;

        if ($request->end_mileage < $vehicle->current_mileage) {
            return response()->json(['message' => 'Le kilométrage de fin ne peut pas être inférieur au compteur.'], 422);
        }

        $assignment->update([
            'status' => AssignmentStatus::Completed,
            'end_mileage' => $request->end_mileage,
            'ended_at' => now(),
            'notes' => $request->notes ?? $assignment->notes,
        ]);

        $vehicle->update([
            'status' => VehicleStatus::Available,
            'current_mileage' => $request->end_mileage,
        ]);

        return response()->json([
            'message' => 'Mission terminée.',
            'assignment' => $assignment->fresh()->load('vehicle'),
        ]);
    }
}
