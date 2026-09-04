<?php

namespace App\Http\Controllers\Admin;

use App\Admin\DTOs\ReportFilterDto;
use App\Admin\Queries\AdminDashboardQuery;
use App\Admin\Reports\ReportFactory;
use App\Admin\Services\AuditLogger;
use App\Admin\Services\PlatformStatsAggregator;
use App\Admin\Support\AdminDashboardFilterBuilder;
use App\Admin\Support\ReportFilterBuilder;
use App\Enums\AuditActionType;
use App\Enums\ReportExportStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\ExportReportPdfJob;
use App\Models\Donation;
use App\Models\MatchRecord;
use App\Models\FoodRequest;
use App\Models\Pickup;
use App\Models\PlatformStat;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        ReportFactory $reportFactory,
        PlatformStatsAggregator $aggregator,
        AuditLogger $auditLogger,
    ): View {
        $aggregator->aggregate();
        $platformStat = PlatformStat::query()->latest('period_start')->first();

        $auditLogger->log(
            actor: $request->user(),
            actionType: AuditActionType::StatsViewed,
            metadata: ['source' => 'admin_dashboard'],
        );

        $activeTab = $request->string('tab', 'overview')->toString();
        $dashboardFilters = AdminDashboardFilterBuilder::fromRequest($request);
        $dashboardQuery = new AdminDashboardQuery($dashboardFilters);

        $reportType = ReportType::tryFrom($request->string('type', ReportType::Donations->value)->toString())
            ?? ReportType::Donations;

        $reportFilters = $activeTab === 'reports'
            ? ReportFilterBuilder::fromRequest($request, $reportType)
            : new ReportFilterDto(type: $reportType);
        $report = $reportFactory->make($reportFilters);
        $reportRows = $report->paginate(15);

        $overviewMetrics = $dashboardFilters->hasFilters()
            ? $dashboardQuery->overviewMetrics()
            : ($platformStat?->metrics ?? $dashboardQuery->overviewMetrics());

        return view('admin.dashboard', [
            'platformStat' => $platformStat,
            'overviewMetrics' => $overviewMetrics,
            'dashboardFilters' => $dashboardFilters,
            'activeTab' => $activeTab,
            'reportType' => $reportType,
            'reportRows' => $reportRows,
            'reportColumns' => $report->getColumns(),
            'filters' => $reportFilters,
            'counts' => [
                'users' => User::query()->count(),
                'donations' => Donation::query()->count(),
                'requests' => FoodRequest::query()->count(),
                'matches' => MatchRecord::query()->count(),
                'pickups' => Pickup::query()->count(),
            ],
            'users' => $dashboardQuery->users()->paginate(10)->withQueryString(),
            'donations' => $dashboardQuery->donations()->paginate(10)->withQueryString(),
            'requests' => $dashboardQuery->requests()->paginate(10)->withQueryString(),
            'matches' => $dashboardQuery->matches()->paginate(10)->withQueryString(),
            'pickups' => $dashboardQuery->pickups()->paginate(10)->withQueryString(),
            'auditLogs' => $dashboardQuery->auditLogs()->paginate(10)->withQueryString(),
            'reportTypes' => ReportType::cases(),
            'donationStatuses' => ['available', 'expiring_soon', 'reserved', 'completed', 'expired', 'cancelled'],
            'requestStatuses' => ['pending', 'matched', 'partial', 'withdrawn'],
            'matchStatuses' => ['confirmed'],
            'pickupStatuses' => ['scheduled', 'confirmed', 'completed', 'cancelled', 'expired_pickup'],
            'userRoles' => UserRole::cases(),
            'auditActionTypes' => AuditActionType::cases(),
        ]);
    }

    public function export(Request $request, AuditLogger $auditLogger): RedirectResponse|StreamedResponse
    {
        $reportType = ReportType::tryFrom($request->string('type')->toString());

        if ($reportType === null) {
            return back()->withErrors(['type' => 'Please select a valid report type.']);
        }

        $filters = ReportFilterBuilder::fromRequest($request, $reportType, perPage: 1000);

        $export = ReportExport::query()->create([
            'requested_by' => $request->user()->id,
            'report_type' => $filters->type,
            'filters' => $filters->toArray(),
            'status' => ReportExportStatus::Pending,
        ]);

        ExportReportPdfJob::dispatchSync($export->id, $request->user()->id);

        $export->refresh();

        if ($export->status !== ReportExportStatus::Completed || $export->file_path === null) {
            return back()->withErrors(['export' => 'PDF export failed. Please try again.']);
        }

        return Storage::disk('local')->download(
            $export->file_path,
            "foodbridge-{$export->report_type->value}-report.pdf",
        );
    }
}