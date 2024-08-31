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
            'image' => ['required', 'image'],
            'price' => ['required', 'integer'],
            'old_price' => ['required', 'integer'],
            'access_link' => ['required', 'string'],
            'type' => ['required', 'string', 'in:' . ProductType::EBOOK->value . ',' . ProductType::MENTORSHIP->value],
            'is_affiliated' => ['boolean']
        ];
    }
}
