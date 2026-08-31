<?php

namespace App\Admin\Reports\Base;

use App\Enums\ReportType;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;

class DonationsReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return Donation::query()->with('donor:id,name,email');
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['id', 'user_id', 'category', 'region', 'quantity', 'unit', 'status', 'expires_at', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Donations;
    }
}
