<?php

namespace App\Admin\Support;

use App\Admin\DTOs\AdminDashboardFilterDto;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminDashboardFilterBuilder
{
    public static function fromRequest(Request $request): AdminDashboardFilterDto
    {
        return new AdminDashboardFilterDto(
            search: $request->filled('search') ? $request->string('search')->trim()->toString() : null,
            from: $request->filled('from') ? Carbon::parse($request->string('from'))->startOfDay() : null,
            to: $request->filled('to') ? Carbon::parse($request->string('to'))->endOfDay() : null,
            status: $request->filled('status') ? $request->string('status')->toString() : null,
            category: $request->filled('category') ? $request->string('category')->toString() : null,
            region: $request->filled('region') ? $request->string('region')->toString() : null,
            role: $request->filled('role') ? $request->string('role')->toString() : null,
            isActive: $request->has('is_active') && $request->input('is_active') !== ''
                ? $request->boolean('is_active')
                : null,
            actionType: $request->filled('action_type') ? $request->string('action_type')->toString() : null,
        );
    }
}
