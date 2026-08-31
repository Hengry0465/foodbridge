<?php

namespace App\Admin\Reports\Base;

use App\Enums\ReportType;
use App\Models\Pickup;
use Illuminate\Database\Eloquent\Builder;

class PickupsReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return Pickup::query()->with('donationMatch:id,donation_id,request_id');
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['id', 'match_id', 'scheduled_at', 'status', 'completed_at', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Pickups;
    }
}
