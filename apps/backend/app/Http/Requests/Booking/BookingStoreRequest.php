<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Guests are allowed to create bookings.
        return true;
    }

    public function rules(): array
    {
        return [
            // quantity is always required
            'quantity' => ['required', 'integer', 'min:1'],

            // guest identity fields; required only when no authenticated user
            'guest_name'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'guest_email' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Add conditional validation: if no user, guest_name & guest_email are required.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if (!$this->user()) {
                if (! $this->filled('guest_name')) {
                    $v->errors()->add('guest_name', __('bookings.guest_name_required'));
                }
                if (! $this->filled('guest_email')) {
                    $v->errors()->add('guest_email', __('bookings.guest_email_required'));
                }
            }
        });
    }
}
