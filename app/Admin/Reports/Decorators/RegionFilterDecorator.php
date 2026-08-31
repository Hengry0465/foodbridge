<?php

namespace App\Admin\Reports\Decorators;

use App\Admin\Reports\Contracts\ReportInterface;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Builder;

class RegionFilterDecorator extends AbstractReportDecorator
{
    public function __construct(
        ReportInterface $report,
        private ?string $region,
    ) {
        parent::__construct($report);
    }

    public function getQuery(): Builder
    {
        $query = $this->report->getQuery();

        if ($this->region === null) {
            return $query;
        }

        return match ($this->report->getType()) {
            ReportType::Donations, ReportType::Requests, ReportType::Users => $query->where('region', $this->region),
            ReportType::Matches => $query->whereHas(
                'donation',
                fn (Builder $builder): Builder => $builder->where('region', $this->region),
            ),
            ReportType::Pickups => $query->whereHas(
                'donationMatch.donation',
                fn (Builder $builder): Builder => $builder->where('region', $this->region),
            ),
            ReportType::AuditLogs => $query,
        };
    }
}
