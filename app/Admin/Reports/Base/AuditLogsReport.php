<?php

namespace App\Admin\Reports\Base;

use App\Enums\ReportType;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsReport extends BaseReport
{
    protected function baseQuery(): Builder
    {
        return AuditLog::query()->with('actor:id,name,email');
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return ['id', 'actor_id', 'action_type', 'target_table', 'target_id', 'before_value', 'after_value', 'created_at'];
    }

    public function getType(): ReportType
    {
        return ReportType::AuditLogs;
    }
}
