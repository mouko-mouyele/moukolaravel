<?php

namespace App\Enums;

enum AlertType: string
{
    case MaintenanceDue = 'maintenance_due';
    case OilChange = 'oil_change';
    case InsuranceExpiry = 'insurance_expiry';
    case TechnicalInspection = 'technical_inspection';
    case MileageThreshold = 'mileage_threshold';

    public function label(): string
    {
        return match ($this) {
            self::MaintenanceDue => 'Entretien à prévoir',
            self::OilChange => 'Vidange à venir',
            self::InsuranceExpiry => 'Renouvellement assurance',
            self::TechnicalInspection => 'Contrôle technique',
            self::MileageThreshold => 'Seuil kilométrique',
        };
    }
}
