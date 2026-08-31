<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class ExportReportRequest extends IndexReportRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'format' => ['nullable', 'string', Rule::in(['pdf'])],
        ]);
    }
}
