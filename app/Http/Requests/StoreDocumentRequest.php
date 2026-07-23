<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = config('autochain.documents.max_size_kb', 10240);

        return [
            'type' => ['required', 'in:registration_card,insurance,invoice,technical_inspection,other'],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max($maxKb)],
            'expiry_date' => ['nullable', 'date'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
