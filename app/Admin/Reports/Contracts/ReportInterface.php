<?php

namespace App\Admin\Reports\Contracts;

use App\Enums\ReportType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface ReportInterface
{
    public function getQuery(): Builder;

    /**
     * @return list<string>
     */
    public function getColumns(): array;

    public function getType(): ReportType;

    public function paginate(int $perPage = 25): LengthAwarePaginator;
}
