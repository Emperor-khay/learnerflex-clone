<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDigitalProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string'],
            'description' => ['sometimes', 'required', 'string'],
            'price' => ['sometimes', 'required', 'numeric'],
            'type' => ['sometimes', 'required', 'string', 'in:' . ProductType::DIGITAL->value],
            'commission' => ['sometimes', 'required', 'numeric', 'between:1,90'],
            'contact_email' => ['nullable', 'string', 'email'],
            'access_link' => ['sometimes', 'required', 'string'],
            'vsl_pa_link' => ['sometimes', 'nullable', 'string'],
            'promotional_material' => ['sometimes', 'nullable', 'string'],
            'sale_page_link' => ['sometimes', 'required', 'string'],
            'sale_challenge_link' => ['sometimes', 'nullable', 'string'],
            'x_link' => ['sometimes', 'nullable', 'string'],
            'ig_link' => ['sometimes', 'nullable', 'string'],
            'yt_link' => ['sometimes', 'nullable', 'string'],
            'fb_link' => ['sometimes', 'nullable', 'string'],
            'tt_link' => ['sometimes', 'nullable', 'string'],
            'images.*' => ['image', 'max:7000'], // Validate each image
            'images' => ['array', 'max:5'],      // Limit to 5 images
        ];
    }
}
