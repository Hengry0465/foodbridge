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

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['id', 'name', 'email', 'region', 'role', 'is_active', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::Users;
    }
}
