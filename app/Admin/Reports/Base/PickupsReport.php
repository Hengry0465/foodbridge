<?php

namespace App\Admin\Reports\Base;

use App\Enums\ReportType;
use App\Models\Pickup;
use Illuminate\Database\Eloquent\Builder;

class PickupsReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return Pickup::query()->with(['match:id,donation_id,request_id', 'status:id,code']);
    }

    public function getColumns(): array
    {
        return ['id', 'match_id', 'scheduled_at', 'status.code', 'completed_at', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Pickups;
    }
}
