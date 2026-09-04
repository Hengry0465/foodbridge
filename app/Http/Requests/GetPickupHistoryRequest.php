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
 * Form Request validation for getting pickup history
 */
class GetPickupHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in policy
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:scheduled,confirmed,completed,cancelled,expired_pickup',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status value',
            'date_from.date' => 'Invalid date format for date_from',
            'date_to.date' => 'Invalid date format for date_to',
            'date_to.after_or_equal' => 'date_to must be after or equal to date_from',
            'page.min' => 'Page must be at least 1',
            'per_page.min' => 'per_page must be at least 1',
            'per_page.max' => 'per_page must not exceed 100',
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
