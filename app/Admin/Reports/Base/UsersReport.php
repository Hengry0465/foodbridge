<?php
namespace App\Admin\Reports\Base;
use App\Enums\ReportType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsersReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return User::query();
    }

    public function getColumns(): array
    {
        return ['id', 'name', 'email', 'role', 'is_active', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Users;
    }
}