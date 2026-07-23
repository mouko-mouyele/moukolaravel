<?php

namespace App\Services;

use App\Enums\AlertType;
use App\Enums\UserRole;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class AlertEngineService
{
    public function generateForFleet(): Collection
    {
        $created = collect();

        Vehicle::query()->each(function (Vehicle $vehicle) use ($created) {
            $created = $created->merge($this->generateForVehicle($vehicle));
        });

        return $created->filter();
    }

    public function generateForVehicle(Vehicle $vehicle): Collection
    {
        $created = collect();
        $config = config('autochain.alerts');

        if ($vehicle->insurance_expiry) {
            $daysUntil = Carbon::today()->diffInDays($vehicle->insurance_expiry, false);
            if ($daysUntil >= 0 && $daysUntil <= $config['insurance_days_before']) {
                $created->push($this->createIfNotExists(
                    $vehicle,
                    AlertType::InsuranceExpiry,
                    'Renouvellement assurance',
                    "L'assurance du véhicule {$vehicle->license_plate} expire le {$vehicle->insurance_expiry->format('d/m/Y')}.",
                    $vehicle->insurance_expiry
                ));
            }
        }

        if ($vehicle->technical_inspection_expiry) {
            $daysUntil = Carbon::today()->diffInDays($vehicle->technical_inspection_expiry, false);
            if ($daysUntil >= 0 && $daysUntil <= $config['inspection_days_before']) {
                $created->push($this->createIfNotExists(
                    $vehicle,
                    AlertType::TechnicalInspection,
                    'Contrôle technique à prévenir',
                    "Le contrôle technique du véhicule {$vehicle->license_plate} expire le {$vehicle->technical_inspection_expiry->format('d/m/Y')}.",
                    $vehicle->technical_inspection_expiry
                ));
            }
        }

        if ($vehicle->next_oil_change_km && $vehicle->current_mileage >= ($vehicle->next_oil_change_km - $config['oil_change_km_before'])) {
            $created->push($this->createIfNotExists(
                $vehicle,
                AlertType::OilChange,
                'Vidange à venir',
                "Le véhicule {$vehicle->license_plate} approche du kilométrage de vidange ({$vehicle->next_oil_change_km} km).",
                null,
                $vehicle->next_oil_change_km
            ));
        }

        if ($vehicle->next_maintenance_km && $vehicle->current_mileage >= ($vehicle->next_maintenance_km - $config['maintenance_km_before'])) {
            $created->push($this->createIfNotExists(
                $vehicle,
                AlertType::MaintenanceDue,
                'Entretien à prévoir',
                "Le véhicule {$vehicle->license_plate} approche du kilométrage d'entretien ({$vehicle->next_maintenance_km} km).",
                null,
                $vehicle->next_maintenance_km
            ));
        }

        return $created->filter();
    }

    private function createIfNotExists(
        Vehicle $vehicle,
        AlertType $type,
        string $title,
        string $message,
        ?Carbon $dueDate = null,
        ?int $dueMileage = null
    ): ?Alert {
        $exists = Alert::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('type', $type)
            ->where('is_resolved', false)
            ->exists();

        if ($exists) {
            return null;
        }

        $alert = Alert::create([
            'vehicle_id' => $vehicle->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'due_date' => $dueDate,
            'due_mileage' => $dueMileage,
        ]);

        $this->notifyManagers($alert);

        return $alert;
    }

    private function notifyManagers(Alert $alert): void
    {
        $alert->load('vehicle');

        User::query()
            ->whereIn('role', [UserRole::FleetManager, UserRole::SuperAdmin])
            ->where('is_active', true)
            ->each(function (User $user) use ($alert) {
                try {
                    Mail::to($user->email)->send(new AlertNotificationMail($alert));
                } catch (\Throwable) {
                    // Mail non configuré (log driver) — alerte reste en base
                }
            });
    }
}
