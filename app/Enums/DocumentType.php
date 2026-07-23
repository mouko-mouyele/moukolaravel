<?php

namespace App\Enums;

enum DocumentType: string
{
    case RegistrationCard = 'registration_card';
    case Insurance = 'insurance';
    case Invoice = 'invoice';
    case TechnicalInspection = 'technical_inspection';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationCard => 'Carte grise',
            self::Insurance => 'Assurance',
            self::Invoice => 'Facture',
            self::TechnicalInspection => 'Contrôle technique',
            self::Other => 'Autre',
        };
    }
}
