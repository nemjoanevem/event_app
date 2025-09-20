<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserToggleEnabledRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route guarded
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }
}
