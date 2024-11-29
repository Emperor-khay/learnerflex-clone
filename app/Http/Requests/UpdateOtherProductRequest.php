<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOtherProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'images' => ['sometimes', 'array', 'max:5'], // Multiple images allowed, max 5
            'images.*' => ['image', 'max:7000'], // Validate each image
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:20000'], // Optional eBook file upload
            'price' => ['sometimes', 'nullable', 'numeric'],
            'old_price' => ['sometimes', 'nullable', 'numeric'],
            'access_link' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'nullable', 'string', 'in:ebook,mentorship'],
            'commission' => ['sometimes', 'nullable', 'string'],
            'contact_email' => ['sometimes', 'nullable', 'string', 'email'],
            'vsl_pa_link' => ['nullable', 'string'],
            'promotional_material' => ['nullable', 'string'],
            'sale_page_link' => ['sometimes', 'nullable', 'string'],
            'sale_challenge_link' => ['nullable', 'string'],
            'x_link' => ['nullable', 'string'],
            'ig_link' => ['nullable', 'string'],
            'yt_link' => ['nullable', 'string'],
            'fb_link' => ['nullable', 'string'],
            'tt_link' => ['nullable', 'string'],
            'is_affiliated' => ['sometimes', 'boolean'],
        ];

    }
}
