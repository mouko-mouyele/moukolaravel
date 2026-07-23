<?php

namespace App\Http\Controllers\Api;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $fleetStats = [
            'total' => Vehicle::count(),
            'available' => Vehicle::where('status', VehicleStatus::Available)->count(),
            'in_mission' => Vehicle::where('status', VehicleStatus::InMission)->count(),
            'in_maintenance' => Vehicle::where('status', VehicleStatus::InMaintenance)->count(),
            'out_of_service' => Vehicle::where('status', VehicleStatus::OutOfService)->count(),
        ];

        $alertsCount = Alert::where('is_resolved', false)->count();
        $activeAssignments = VehicleAssignment::where('status', 'active')->count();

        $recentAlerts = Alert::with('vehicle:id,license_plate')
            ->where('is_resolved', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $maintenanceCosts = DB::table('maintenances')
            ->selectRaw('SUM(cost) as total, COUNT(*) as count')
            ->first();

        return response()->json([
            'project' => config('autochain.name'),
            'author' => config('autochain.author'),
            'fleet' => $fleetStats,
            'alerts_unresolved' => $alertsCount,
            'active_assignments' => $activeAssignments,
            'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role'),
            'maintenance_costs' => [
                'total' => (float) ($maintenanceCosts->total ?? 0),
                'operations_count' => (int) ($maintenanceCosts->count ?? 0),
            ],
            'recent_alerts' => $recentAlerts,
        ]);
    }
}
