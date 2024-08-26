<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProductType;

class DigitalProductRequest extends FormRequest
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
            'description' => ['required', 'string', 'min:10', 'max:500'],
            'price' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:' . ProductType::DIGITAL->value],
            'commission' => ['required', 'string'],
            'contact_email' => ['required', 'string', 'email'],
            'access_link' => ['required', 'string'],
            'vsl_pa_link' => ['nullable', 'string'],
            'promotional_material' => ['nullable', 'string'],
            'sale_page_link' => ['required', 'string'],
            'sale_challenge_link' => ['nullable', 'string'],
            'x_link' => ['nullable', 'string'],
            'ig_link' => ['nullable', 'string'],
            'yt_link' => ['nullable', 'string'],
            'fb_link' => ['nullable', 'string'],
            'tt_link' => ['nullable', 'string'],
        ];
    }
}
