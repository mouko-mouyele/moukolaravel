<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Maintenance;
use App\Models\MileageReading;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Support\Collection;

class TimelineService
{
    public function forVehicle(Vehicle $vehicle): Collection
    {
        $events = collect();

        $vehicle->maintenances()->with('mechanic')->get()->each(function (Maintenance $m) use ($events) {
            $events->push([
                'type' => 'maintenance',
                'source' => $m->certified_on_chain ? 'blockchain' : 'backend',
                'date' => $m->service_date->toIso8601String(),
                'title' => $m->intervention_type,
                'description' => $m->description,
                'certified' => $m->certified_on_chain,
                'tx_hash' => $m->blockchain_tx_hash,
                'data' => [
                    'mileage' => $m->mileage_at_service,
                    'cost' => $m->cost,
                    'parts_changed' => $m->parts_changed,
                    'mechanic' => $m->mechanic?->name,
                ],
            ]);
        });

        $vehicle->mileageReadings()->with('recordedBy')->get()->each(function (MileageReading $r) use ($events) {
            $events->push([
                'type' => 'mileage',
                'source' => $r->certified_on_chain ? 'blockchain' : 'backend',
                'date' => $r->recorded_at->toIso8601String(),
                'title' => 'Relevé kilométrique',
                'description' => $r->notes,
                'certified' => $r->certified_on_chain,
                'tx_hash' => $r->blockchain_tx_hash,
                'data' => [
                    'mileage' => $r->mileage,
                    'recorded_by' => $r->recordedBy?->name,
                ],
            ]);
        });

        $vehicle->documents()->get()->each(function (Document $d) use ($events) {
            $events->push([
                'type' => 'document',
                'source' => 'backend',
                'date' => $d->created_at->toIso8601String(),
                'title' => $d->title,
                'description' => $d->type->label(),
                'certified' => false,
                'tx_hash' => null,
                'data' => [
                    'document_type' => $d->type->value,
                    'file_hash' => $d->file_hash,
                    'expiry_date' => $d->expiry_date?->format('Y-m-d'),
                ],
            ]);
        });

        $vehicle->assignments()->with(['driver', 'assignedBy'])->get()->each(function (VehicleAssignment $a) use ($events) {
            $events->push([
                'type' => 'assignment',
                'source' => 'backend',
                'date' => ($a->started_at ?? $a->created_at)->toIso8601String(),
                'title' => 'Affectation chauffeur',
                'description' => $a->notes,
                'certified' => ! empty($a->blockchain_tx_hash),
                'tx_hash' => $a->blockchain_tx_hash,
                'data' => [
                    'driver' => $a->driver?->name,
                    'status' => $a->status->value,
                    'start_mileage' => $a->start_mileage,
                    'end_mileage' => $a->end_mileage,
                ],
            ]);
        });

        return $events->sortByDesc('date')->values();
    }
}
