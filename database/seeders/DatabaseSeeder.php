<?php

namespace Database\Seeders;

use App\Enums\AssignmentStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@autochain.local'],
            [
                'name' => 'Moïse Admin',
                'password' => 'password',
                'role' => UserRole::SuperAdmin,
                'wallet_address' => '0x6484768ddcb92ed80c012748b576c04429e77b48',
                'is_active' => true,
            ]
        );

        $fleetManager = User::updateOrCreate(
            ['email' => 'gestionnaire@autochain.local'],
            [
                'name' => 'Gestionnaire Parc',
                'password' => 'password',
                'role' => UserRole::FleetManager,
                'is_active' => true,
            ]
        );

        $driver = User::updateOrCreate(
            ['email' => 'chauffeur@autochain.local'],
            [
                'name' => 'Jean Chauffeur',
                'password' => 'password',
                'role' => UserRole::Driver,
                'wallet_address' => '0x3c44cdddb6a900fa2b585dd299e03d12fa4293bc',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'garagiste@autochain.local'],
            [
                'name' => 'Garagiste Agréé',
                'password' => 'password',
                'role' => UserRole::Mechanic,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'auditeur@autochain.local'],
            [
                'name' => 'Auditeur Acheteur',
                'password' => 'password',
                'role' => UserRole::Auditor,
                'wallet_address' => '0x70997970c51812dc3a010c7d01b50b0d17ef88c8',
                'is_active' => true,
            ]
        );

        Vehicle::updateOrCreate(
            ['vin' => 'VF1RJA00123456789'],
            [
                'license_plate' => 'AB-123-CD',
                'brand' => 'Renault',
                'model' => 'Clio V',
                'year' => 2022,
                'fuel_type' => 'essence',
                'current_mileage' => 45000,
                'status' => VehicleStatus::Available,
                'insurance_expiry' => now()->addMonths(2),
                'technical_inspection_expiry' => now()->addMonths(6),
                'next_oil_change_km' => 50000,
                'next_maintenance_km' => 60000,
                'registered_by' => $fleetManager->id,
            ]
        );

        Vehicle::updateOrCreate(
            ['vin' => 'WBA8E9G50JNU12345'],
            [
                'license_plate' => 'EF-456-GH',
                'brand' => 'BMW',
                'model' => 'Série 3',
                'year' => 2021,
                'fuel_type' => 'diesel',
                'current_mileage' => 78000,
                'status' => VehicleStatus::InMission,
                'insurance_expiry' => now()->addDays(20),
                'technical_inspection_expiry' => now()->addMonths(3),
                'next_oil_change_km' => 80000,
                'next_maintenance_km' => 90000,
                'registered_by' => $fleetManager->id,
            ]
        );

        $bmw = Vehicle::where('license_plate', 'EF-456-GH')->first();

        if ($bmw) {
            VehicleAssignment::updateOrCreate(
                [
                    'vehicle_id' => $bmw->id,
                    'driver_id' => $driver->id,
                    'status' => AssignmentStatus::Active,
                ],
                [
                    'assigned_by' => $fleetManager->id,
                    'start_mileage' => 78000,
                    'started_at' => now(),
                    'notes' => 'Mission demo mobile chauffeur',
                ]
            );
        }

        $this->command?->info('Données AutoChain Emma+ seedées (auteur: Moïse).');
        $this->command?->info('Comptes demo — mot de passe: password');
    }
}
