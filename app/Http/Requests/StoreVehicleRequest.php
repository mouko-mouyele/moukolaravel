<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vin' => ['required', 'string', 'size:17', 'unique:vehicles,vin'],
            'license_plate' => ['required', 'string', 'max:20', 'unique:vehicles,license_plate'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1990', 'max:'.(date('Y') + 1)],
            'fuel_type' => ['required', 'string', 'max:30'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'insurance_expiry' => ['nullable', 'date'],
            'technical_inspection_expiry' => ['nullable', 'date'],
            'next_oil_change_km' => ['nullable', 'integer', 'min:0'],
            'next_maintenance_km' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
