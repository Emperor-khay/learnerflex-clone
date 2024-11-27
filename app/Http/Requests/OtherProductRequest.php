<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProductType;

class OtherProductRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'images.*' => ['image', 'max:7000'], // Validate each image
            'images' => ['array', 'max:5'],      // Limit to 5 images
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:90000'], // eBook file (PDF)
            'price' => ['required', 'integer'],
            'old_price' => ['required', 'integer'],
            'access_link' => ['required', 'string'],
            'type' => ['required', 'string', 'in:' . ProductType::EBOOK->value . ',' . ProductType::MENTORSHIP->value],
            'commission' => ['sometimes', 'nullable', 'numeric', 'between:1,90'],
            'contact_email' => ['nullable', 'string', 'email'],
            'vsl_pa_link' => ['nullable', 'string'],
            'promotional_material' => ['nullable', 'string'],
            'sale_page_link' => ['nullable', 'string'],
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
