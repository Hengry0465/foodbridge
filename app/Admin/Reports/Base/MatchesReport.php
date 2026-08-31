<?php

namespace App\Admin\Reports\Base;

use App\Enums\ReportType;
use App\Models\DonationMatch;
use Illuminate\Database\Eloquent\Builder;

class MatchesReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return DonationMatch::query()->with(['donation:id,category', 'foodRequest:id,category']);
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['id', 'donation_id', 'request_id', 'status', 'matched_at', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Matches;
    }
}
