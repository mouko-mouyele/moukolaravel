<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'in:super_admin,fleet_manager,driver,mechanic,auditor'],
            'phone' => ['nullable', 'string', 'max:20'],
            'wallet_address' => ['nullable', 'string', 'size:42', Rule::unique('users', 'wallet_address')->ignore($userId)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
