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
        return false;
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
            'image' => ['sometimes', 'image'], // Make image optional for updates
            'price' => ['sometimes', 'required', 'numeric'],
            'old_price' => ['sometimes', 'required', 'numeric'],
            'access_link' => ['sometimes', 'required', 'string'],
            'type' => ['sometimes', 'required', 'string', 'in:' . ProductType::EBOOK->value . ',' . ProductType::MENTORSHIP->value],
            'is_affiliated' => ['sometimes', 'boolean'],
        ];
    }
}
