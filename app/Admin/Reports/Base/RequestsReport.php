<?php
namespace App\Admin\Reports\Base;
use App\Enums\ReportType;
use App\Models\FoodRequest;
use Illuminate\Database\Eloquent\Builder;

class RequestsReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return FoodRequest::query()->with(['recipient:id,name,email', 'preferredDonation:id,food_name']);
    }

    public function getColumns(): array
    {
        return ['id', 'recipient_id', 'preferred_donation_id', 'category', 'status', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Requests;
    }
}