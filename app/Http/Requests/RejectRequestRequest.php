<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by service layer
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => 'required|string|max:1000',
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
            'comment.required' => 'A reason is required when rejecting a request',
            'comment.max' => 'Comment must not exceed 1000 characters',
        ];
    }
}
