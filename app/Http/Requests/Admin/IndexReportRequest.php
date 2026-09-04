<?php

namespace App\Http\Requests\Admin;

use App\Enums\FoodCategory;
use App\Enums\FoodRegion;
use App\Enums\ReportType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReportType::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', Rule::enum(FoodCategory::class)],
            'region' => ['nullable', Rule::enum(FoodRegion::class)],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'is_active' => ['nullable', 'boolean'],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_type' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
