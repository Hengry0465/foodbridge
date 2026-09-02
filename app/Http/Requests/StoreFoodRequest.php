<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->is('api/*') || $this->user()?->role === 'recipient';
    }

    protected function prepareForValidation(): void
    {
        if ($this->is('api/*')) {
            $this->merge([
                'request_id' => $this->input('requestID', $this->header('X-Request-ID')),
                'request_timestamp' => $this->input('timestamp', $this->header('X-Timestamp')),
            ]);
        }
    }

    public function rules(): array
    {
        $apiRequest = $this->is('api/*');

        return [
            'recipient_id' => [
                $apiRequest ? 'required' : 'prohibited',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'recipient')),
            ],
            'donation_id' => [
                'nullable',
                'integer',
                Rule::exists('donations', 'id')->where(fn ($query) => $query
                    ->where('category', $this->input('category'))
                    ->where('status', 'available')
                    ->where('quantity_available', '>', 0)
                    ->where('expires_at', '>', now())),
            ],
            'category' => ['required', 'string', 'in:Cooked Meals,Bakery,Fresh Produce,Packaged Goods'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'preferred_pickup_at' => ['required', 'date', 'after:now'],
            'request_id' => [$apiRequest ? 'required' : 'nullable', 'string', 'min:8', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'request_timestamp' => [$apiRequest ? 'required' : 'nullable', 'date'],
        ];
    }
}
