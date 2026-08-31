<?php

namespace App\Admin\Reports;

use App\Admin\DTOs\ReportFilterDto;
use App\Admin\Reports\Base\AuditLogsReport;
use App\Admin\Reports\Base\DonationsReport;
use App\Admin\Reports\Base\MatchesReport;
use App\Admin\Reports\Base\PickupsReport;
use App\Admin\Reports\Base\RequestsReport;
use App\Admin\Reports\Base\UsersReport;
use App\Admin\Reports\Contracts\ReportInterface;
use App\Admin\Reports\Decorators\ActionTypeFilterDecorator;
use App\Admin\Reports\Decorators\ActiveStatusFilterDecorator;
use App\Admin\Reports\Decorators\ActorFilterDecorator;
use App\Admin\Reports\Decorators\CategoryFilterDecorator;
use App\Admin\Reports\Decorators\DateRangeFilterDecorator;
use App\Admin\Reports\Decorators\RegionFilterDecorator;
use App\Admin\Reports\Decorators\RoleFilterDecorator;
use App\Admin\Reports\Decorators\StatusFilterDecorator;
use App\Enums\ReportType;

class ReportFactory
{
    public function make(ReportFilterDto $filters): ReportInterface
    {
        $report = $this->createBaseReport($filters->type);

        if ($filters->from !== null || $filters->to !== null) {
            $report = new DateRangeFilterDecorator($report, $filters->from, $filters->to);
        }

        if ($filters->status !== null) {
            $report = new StatusFilterDecorator($report, $filters->status);
        }

        if ($filters->category !== null) {
            $report = new CategoryFilterDecorator($report, $filters->category);
        }

        if ($filters->region !== null) {
            $report = new RegionFilterDecorator($report, $filters->region);
        }

        if ($filters->role !== null) {
            $report = new RoleFilterDecorator($report, $filters->role);
        }

        if ($filters->isActive !== null) {
            $report = new ActiveStatusFilterDecorator($report, $filters->isActive);
        }

        if ($filters->actorId !== null) {
            $report = new ActorFilterDecorator($report, $filters->actorId);
        }

        if ($filters->actionType !== null) {
            $report = new ActionTypeFilterDecorator($report, $filters->actionType);
        }

        return $report;
    }

    private function createBaseReport(ReportType $type): ReportInterface
    {
        return match ($type) {
            ReportType::Users => new UsersReport,
            ReportType::Donations => new DonationsReport,
            ReportType::Requests => new RequestsReport,
            ReportType::Matches => new MatchesReport,
            ReportType::Pickups => new PickupsReport,
            ReportType::AuditLogs => new AuditLogsReport,
        };
    }
}
