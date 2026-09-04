<?php

namespace App\Admin\Reports\Decorators;

use App\Admin\Reports\Contracts\ReportInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DateRangeFilterDecorator extends AbstractReportDecorator
{
    public function __construct(
        ReportInterface $report,
        private ?Carbon $from,
        private ?Carbon $to,
        private string $column = 'created_at',
    ) {
        parent::__construct($report);
    }

    public function getQuery(): Builder
    {
        $query = $this->report->getQuery();

        if ($this->from !== null) {
            $query->where($this->column, '>=', $this->from);
        }

        if ($this->to !== null) {
            $query->where($this->column, '<=', $this->to);
        }

        return $query;
    }
}
