<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class WalletVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_address' => ['required', 'string', 'size:42'],
            'signature' => ['required', 'string'],
            'message' => ['required', 'string'],
        ];
    }
}
