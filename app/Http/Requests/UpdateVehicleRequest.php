<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id ?? $this->route('vehicle');

        return [
            'license_plate' => ['sometimes', 'string', 'max:20', Rule::unique('vehicles', 'license_plate')->ignore($vehicleId)],
            'brand' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],
            'year' => ['sometimes', 'integer', 'min:1990', 'max:'.(date('Y') + 1)],
            'fuel_type' => ['sometimes', 'string', 'max:30'],
            'current_mileage' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:available,in_mission,in_maintenance,out_of_service,sold'],
            'insurance_expiry' => ['nullable', 'date'],
            'technical_inspection_expiry' => ['nullable', 'date'],
            'next_oil_change_km' => ['nullable', 'integer', 'min:0'],
            'next_maintenance_km' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
