<?php

namespace App\Services;

use App\Models\FuelRecord;
use App\Models\Vehicle;

class ConsumptionService
{
    public function calculateForRecord(FuelRecord $record): ?float
    {
        $previous = FuelRecord::query()
            ->where('vehicle_id', $record->vehicle_id)
            ->where('id', '<', $record->id)
            ->orderByDesc('mileage_at_fill')
            ->first();

        if (! $previous) {
            return null;
        }

        $distance = $record->mileage_at_fill - $previous->mileage_at_fill;

        if ($distance <= 0 || (float) $record->liters <= 0) {
            return null;
        }

        return round(($record->liters / $distance) * 100, 2);
    }

    public function averageForVehicle(Vehicle $vehicle, int $limit = 10): ?float
    {
        $records = $vehicle->fuelRecords()
            ->whereNotNull('consumption_per_100km')
            ->orderByDesc('filled_at')
            ->limit($limit)
            ->pluck('consumption_per_100km');

        if ($records->isEmpty()) {
            return null;
        }

        return round($records->avg(), 2);
    }
}
