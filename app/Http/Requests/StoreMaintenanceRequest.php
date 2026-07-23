<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'intervention_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mileage_at_service' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'parts_changed' => ['nullable', 'array'],
            'parts_changed.*.name' => ['required_with:parts_changed', 'string'],
            'parts_changed.*.reference' => ['nullable', 'string'],
            'parts_changed.*.quantity' => ['nullable', 'integer', 'min:1'],
            'service_date' => ['required', 'date'],
            'certify_on_chain' => ['nullable', 'boolean'],
            'on_chain_tx_hash' => ['nullable', 'string'],
        ];
    }
}
