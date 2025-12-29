<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by auth middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category' => 'required|in:EQUIPMENT,SOFTWARE,SERVICE,TRAVEL',
            'amount' => 'required|integer|min:0|max:999999999',
            'vendor_name' => 'nullable|string|max:255',
            'urgency' => 'required|in:NORMAL,URGENT',
            'urgency_reason' => 'nullable|string|max:1000',
            'travel_start_date' => 'nullable|date|after_or_equal:today',
            'travel_end_date' => 'nullable|date|after_or_equal:travel_start_date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Request title is required',
            'description.required' => 'Request description is required',
            'category.required' => 'Request category is required',
            'category.in' => 'Invalid category. Must be one of: EQUIPMENT, SOFTWARE, SERVICE, TRAVEL',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be a positive number',
            'amount.max' => 'Amount exceeds maximum allowed value',
            'urgency.required' => 'Urgency level is required',
            'urgency.in' => 'Invalid urgency level. Must be NORMAL or URGENT',
            'travel_start_date.after_or_equal' => 'Travel start date must be today or a future date',
            'travel_end_date.after_or_equal' => 'Travel end date must be on or after start date',
        ];
    }
}
