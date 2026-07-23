<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function vehiclesCsv(): StreamedResponse
    {
        $vehicles = Vehicle::query()->orderBy('license_plate')->get();

        return $this->csvResponse('flotte-vehicules.csv', [
            'Immatriculation', 'VIN', 'Marque', 'Modèle', 'Année', 'Km', 'Statut', 'Carburant',
        ], $vehicles->map(fn (Vehicle $v) => [
            $v->license_plate,
            $v->vin,
            $v->brand,
            $v->model,
            $v->year,
            $v->current_mileage,
            $v->status instanceof \BackedEnum ? $v->status->value : $v->status,
            $v->fuel_type,
        ]));
    }

    public function alertsCsv(): StreamedResponse
    {
        $alerts = Alert::query()->with('vehicle:id,license_plate')->orderByDesc('created_at')->get();

        return $this->csvResponse('alertes.csv', [
            'Titre', 'Type', 'Véhicule', 'Message', 'Résolue', 'Lu', 'Échéance', 'Créée le',
        ], $alerts->map(fn (Alert $a) => [
            $a->title,
            $a->type instanceof \BackedEnum ? $a->type->value : $a->type,
            $a->vehicle?->license_plate,
            $a->message,
            $a->is_resolved ? 'Oui' : 'Non',
            $a->is_read ? 'Oui' : 'Non',
            $a->due_date?->format('Y-m-d'),
            $a->created_at?->format('Y-m-d H:i'),
        ]));
    }

    public function maintenancesCsv(): StreamedResponse
    {
        $rows = Maintenance::query()
            ->with(['vehicle:id,license_plate', 'mechanic:id,name'])
            ->orderByDesc('service_date')
            ->get();

        return $this->csvResponse('maintenances.csv', [
            'Véhicule', 'Intervention', 'Date', 'Km', 'Coût €', 'Garagiste', 'Certifié chain',
        ], $rows->map(fn (Maintenance $m) => [
            $m->vehicle?->license_plate,
            $m->intervention_type,
            $m->service_date?->format('Y-m-d'),
            $m->mileage_at_service,
            $m->cost,
            $m->mechanic?->name,
            $m->certified_on_chain ? 'Oui' : 'Non',
        ]));
    }

    public function fleetReportHtml(): string
    {
        $vehicles = Vehicle::query()->orderBy('license_plate')->get();
        $alerts = Alert::query()->where('is_resolved', false)->with('vehicle:id,license_plate')->limit(20)->get();
        $maintenanceTotal = Maintenance::query()->sum('cost');
        $maintenanceCount = Maintenance::query()->count();

        return view('exports.fleet-report', [
            'project' => config('autochain.name'),
            'author' => config('autochain.author'),
            'generatedAt' => now()->format('d/m/Y H:i'),
            'vehicles' => $vehicles,
            'alerts' => $alerts,
            'maintenanceTotal' => $maintenanceTotal,
            'maintenanceCount' => $maintenanceCount,
        ])->render();
    }

    private function csvResponse(string $filename, array $headers, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
