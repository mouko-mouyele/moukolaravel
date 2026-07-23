<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case InMission = 'in_mission';
    case InMaintenance = 'in_maintenance';
    case OutOfService = 'out_of_service';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::InMission => 'En mission',
            self::InMaintenance => 'En maintenance',
            self::OutOfService => 'Hors service',
            self::Sold => 'Vendu',
        };
    }
}
