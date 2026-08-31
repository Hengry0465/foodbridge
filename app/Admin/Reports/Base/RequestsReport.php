<?php

namespace App\Admin\Reports\Base;

use App\Enums\ReportType;
use App\Models\FoodRequest;
use Illuminate\Database\Eloquent\Builder;

class RequestsReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return FoodRequest::query()->with(['recipient:id,name,email', 'donation:id,category']);
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['id', 'user_id', 'donation_id', 'category', 'region', 'status', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Requests;
    }
}
