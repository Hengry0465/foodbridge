<?php
namespace App\Admin\Reports\Base;
use App\Enums\ReportType;
use App\Models\MatchRecord;
use Illuminate\Database\Eloquent\Builder;

class MatchesReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return MatchRecord::query()->with(['donation:id,category_id,food_name', 'foodRequest:id,category']);
    }

    public function getColumns(): array
    {
        return ['id', 'donation_id', 'request_id', 'quantity_allocated', 'status', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Matches;
    }
}