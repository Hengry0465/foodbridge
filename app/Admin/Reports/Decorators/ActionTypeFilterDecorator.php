<?php

namespace App\Admin\Reports\Decorators;

use App\Admin\Reports\Contracts\ReportInterface;
use Illuminate\Database\Eloquent\Builder;

class ActionTypeFilterDecorator extends AbstractReportDecorator
{
    public function __construct(
        ReportInterface $report,
        private ?string $actionType,
    ) {
        parent::__construct($report);
    }

    public function getQuery(): Builder
    {
        $query = $this->report->getQuery();

        if ($this->actionType !== null) {
            $query->where('action_type', $this->actionType);
        }

        return $query;
    }
}
