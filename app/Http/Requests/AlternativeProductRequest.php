<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;

class AlternativeProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a product.
     */
    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string'],
            'description' => ['required', 'string', 'min:10', 'max:500'],
            'price' => ['required', 'numeric'],
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

    /**
     * Get the validation rules for updating a product.
     */
    public function updateRules(): array
    {
        return [
            'name' => ['string'],
            'description' => ['string', 'min:10', 'max:500'],
            'price' => ['numeric'],
            'type' => ['string', 'in:' . ProductType::DIGITAL->value],
            'commission' => ['string'],
            'contact_email' => ['string', 'email'],
            'access_link' => ['string'],
            'vsl_pa_link' => ['nullable', 'string'],
            'promotional_material' => ['nullable', 'string'],
            'sale_page_link' => ['string'],
            'sale_challenge_link' => ['nullable', 'string'],
            'x_link' => ['nullable', 'string'],
            'ig_link' => ['nullable', 'string'],
            'yt_link' => ['nullable', 'string'],
            'fb_link' => ['nullable', 'string'],
            'tt_link' => ['nullable', 'string'],
        ];
    }

    /**
     * Determine which rule to apply based on the request method.
     */
    public function rules(): array
    {
        return $this->isMethod('post') ? $this->storeRules() : $this->updateRules();
    }
}
