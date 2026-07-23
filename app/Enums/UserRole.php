<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case FleetManager = 'fleet_manager';
    case Driver = 'driver';
    case Mechanic = 'mechanic';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::FleetManager => 'Gestionnaire de parc',
            self::Driver => 'Chauffeur',
            self::Mechanic => 'Garagiste agréé',
            self::Auditor => 'Auditeur / Acheteur',
        };
    }

    public function canWrite(): bool
    {
        return $this !== self::Auditor;
    }
}
