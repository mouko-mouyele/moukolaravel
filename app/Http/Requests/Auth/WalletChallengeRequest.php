<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class WalletChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_address' => ['required', 'string', 'size:42', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ];
    }
}
