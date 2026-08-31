<?php

namespace App\Admin\Reports\Decorators;

use App\Admin\Reports\Contracts\ReportInterface;
use Illuminate\Database\Eloquent\Builder;

class RoleFilterDecorator extends AbstractReportDecorator
{
    public function __construct(
        ReportInterface $report,
        private ?string $role,
    ) {
        parent::__construct($report);
    }

    public function getQuery(): Builder
    {
        $query = $this->report->getQuery();

        if ($this->role !== null) {
            $query->where('role', $this->role);
        }

        return $query;
    }
}
