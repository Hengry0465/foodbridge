<?php
namespace App\Admin\Reports\Base;
use App\Enums\ReportType;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;

class DonationsReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return Donation::query()->with(['donor:id,name,email', 'category:id,name']);
    }

    public function getColumns(): array
    {
        return ['id', 'donor_id', 'category_id', 'quantity', 'unit', 'status', 'expiry_date', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Donations;
    }
}