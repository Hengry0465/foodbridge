<?php
namespace App\Admin\Support;
use App\Admin\DTOs\ReportFilterDto;
use App\Enums\ReportType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportFilterBuilder
{
    public static function fromRequest(Request $request, ReportType $type, int $perPage = 25): ReportFilterDto
    {
        return new ReportFilterDto(
            type: $type,
            from: $request->filled('from') ? Carbon::parse($request->string('from'))->startOfDay() : null,
            to: $request->filled('to') ? Carbon::parse($request->string('to'))->endOfDay() : null,
            status: $request->filled('status') ? $request->string('status')->toString() : null,
            category: $request->filled('category') ? $request->string('category')->toString() : null,
            role: $request->filled('role') ? $request->string('role')->toString() : null,
            isActive: $request->has('is_active') && $request->input('is_active') !== ''
                ? $request->boolean('is_active')
                : null,
            actorId: $request->filled('actor_id') ? $request->integer('actor_id') : null,
            actionType: $request->filled('action_type') ? $request->string('action_type')->toString() : null,
            perPage: $perPage,
        );
    }
}