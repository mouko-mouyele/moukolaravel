<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'buyer_wallet' => ['required', 'string', 'size:42'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'admin_signature' => ['nullable', 'string'],
        ];
    }
}
