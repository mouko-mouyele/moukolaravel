<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'mileage_at_fill' => ['required', 'integer', 'min:0'],
            'liters' => ['required', 'numeric', 'min:0.01'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'filled_at' => ['nullable', 'date'],
            'station' => ['nullable', 'string', 'max:255'],
        ];
    }
}
