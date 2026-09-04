<?php
namespace App\Admin\Reports\Decorators;
use App\Admin\Reports\Contracts\ReportInterface;
use Illuminate\Database\Eloquent\Builder;

class PickupStatusFilterDecorator extends AbstractReportDecorator
{
    public function __construct(
        ReportInterface $report,
        private ?string $status,
    ) {
        parent::__construct($report);
    }

    public function getQuery(): Builder
    {
        $query = $this->report->getQuery();

        if ($this->status !== null) {
            $query->whereHas('status', fn (Builder $builder) => $builder->where('code', $this->status));
        }

        return $query;
    }
}