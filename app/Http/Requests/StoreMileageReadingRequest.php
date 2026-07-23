<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMileageReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'mileage' => ['required', 'integer', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
            'assignment_id' => ['nullable', 'exists:vehicle_assignments,id'],
            'notes' => ['nullable', 'string'],
            'certify_on_chain' => ['nullable', 'boolean'],
            'on_chain_tx_hash' => ['nullable', 'string'],
        ];
    }
}
