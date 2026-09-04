<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Form Request validation for updating pickup status
 */
class UpdatePickupStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in policy
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:scheduled,confirmed,completed,cancelled,expired_pickup',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status value',
            'reason.max' => 'Reason must not exceed 500 characters',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
