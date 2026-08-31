<?php

namespace App\Admin\Reports\Decorators;

use App\Admin\Reports\Contracts\ReportInterface;
use App\Enums\ReportType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractReportDecorator implements ReportInterface
{
    public function __construct(protected ReportInterface $report) {}

    public function getQuery(): Builder
    {
        return $this->report->getQuery();
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return $this->report->getColumns();
    }

    public function getType(): ReportType
    {
        return $this->report->getType();
    }

    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->getQuery()->paginate($perPage);
    }
}
