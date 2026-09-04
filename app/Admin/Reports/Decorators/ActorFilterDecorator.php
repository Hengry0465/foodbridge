<?php

namespace App\Admin\Reports\Decorators;

use App\Admin\Reports\Contracts\ReportInterface;
use Illuminate\Database\Eloquent\Builder;

class ActorFilterDecorator extends AbstractReportDecorator
{
    public function __construct(
        ReportInterface $report,
        private ?int $actorId,
    ) {
        parent::__construct($report);
    }

    public function getQuery(): Builder
    {
        $query = $this->report->getQuery();

        if ($this->actorId !== null) {
            $query->where('actor_id', $this->actorId);
        }

        return $query;
    }
}
