<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:super_admin,fleet_manager,driver,mechanic,auditor'],
            'phone' => ['nullable', 'string', 'max:20'],
            'wallet_address' => ['nullable', 'string', 'size:42', 'unique:users,wallet_address'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
