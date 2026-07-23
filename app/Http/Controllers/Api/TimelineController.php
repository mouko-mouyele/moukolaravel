<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\TimelineService;
use Illuminate\Http\JsonResponse;

class TimelineController extends Controller
{
    public function __construct(private TimelineService $timelineService)
    {
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'vehicle' => $vehicle->only(['id', 'uuid', 'license_plate', 'brand', 'model', 'current_mileage']),
            'timeline' => $this->timelineService->forVehicle($vehicle),
        ]);
    }
}
