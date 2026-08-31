<?php

namespace App\Admin\Reports\Base;

use App\Admin\Reports\Contracts\ReportInterface;
use App\Enums\ReportType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseReport implements ReportInterface
{
    abstract protected function baseQuery(): Builder;

    abstract public function getColumns(): array;

    abstract public function getType(): ReportType;

    public function getQuery(): Builder
    {
        return $this->baseQuery()->latest();
    }

    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->getQuery()->paginate($perPage);
    }
}
