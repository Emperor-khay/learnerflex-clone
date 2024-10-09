<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EbookPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;  // Change to false if you have any authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'currency' => 'required|string',
            'callback_url' => 'required|url',
            'user_id' => 'required|integer',
            'aff_id' => 'nullable|string',
            'product_id' => 'required|integer',
        ];
    }

    /**
     * Custom messages for validation errors (Optional).
     */
    public function messages()
    {
        return [
            'email.required' => 'An email address is required',
            'currency.required' => 'Currency is required',
            'callback_url.required' => 'Callback URL is required',
            'user_id.required' => 'User ID is required',
            'product_id.required' => 'Product ID is required',
        ];
    }
}
