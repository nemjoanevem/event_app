<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route guarded
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'q'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'role'     => ['sometimes', 'nullable', 'in:user,organizer,admin'],
            'enabled'  => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
