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
 * Form Request validation for creating a pickup
 */
class StorePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in policy
    }

    public function rules(): array
    {
        return [
            'match_id' => 'required|integer|exists:food_matches,id',
            'scheduled_at' => 'required|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'match_id.required' => 'Match ID is required',
            'match_id.exists' => 'Match not found',
            'scheduled_at.required' => 'Scheduled time is required',
            'scheduled_at.after' => 'Scheduled time must be in the future',
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
