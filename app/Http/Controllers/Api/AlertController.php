<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = Alert::query()
            ->with('vehicle:id,license_plate,brand,model')
            ->when($request->vehicle_id, fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->when($request->boolean('unread'), fn ($q) => $q->where('is_read', false))
            ->when($request->boolean('unresolved'), fn ($q) => $q->where('is_resolved', false))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($alerts);
    }

    public function markAsRead(Alert $alert): JsonResponse
    {
        $alert->update(['is_read' => true]);

        return response()->json(['message' => 'Alerte marquée comme lue.', 'alert' => $alert]);
    }

    public function resolve(Alert $alert): JsonResponse
    {
        $alert->update([
            'is_resolved' => true,
            'is_read' => true,
            'resolved_at' => now(),
        ]);

        return response()->json(['message' => 'Alerte résolue.', 'alert' => $alert]);
    }
}
