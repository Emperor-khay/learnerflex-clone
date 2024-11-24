<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow any authenticated user to update their profile
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'unique:users,phone,' . auth()->user()->id],
            'country' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:7000'], // Optional image upload, max 2MB
            'bank_name' => ['nullable', 'string'],
            'bank_account' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'size:3'], // ISO currency code
        ];
    }
}
