<?php

namespace App\Jobs;

use App\Admin\DTOs\ReportFilterDto;
use App\Admin\Reports\ReportFactory;
use App\Admin\Services\AuditLogger;
use App\Enums\AuditActionType;
use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportReportPdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $exportId,
        public int $requestedBy,
    ) {}

    public function handle(ReportFactory $reportFactory, AuditLogger $auditLogger): void
    {
        $export = ReportExport::query()->findOrFail($this->exportId);
        $export->update(['status' => ReportExportStatus::Processing]);

        try {
            $filters = $export->filters;
            $report = $reportFactory->make(new ReportFilterDto(
                type: $export->report_type,
                from: isset($filters['from']) ? Carbon::parse($filters['from']) : null,
                to: isset($filters['to']) ? Carbon::parse($filters['to']) : null,
                status: $filters['status'] ?? null,
                category: $filters['category'] ?? null,
                region: $filters['region'] ?? null,
                role: $filters['role'] ?? null,
                isActive: isset($filters['is_active']) ? (bool) $filters['is_active'] : null,
                actorId: isset($filters['actor_id']) ? (int) $filters['actor_id'] : null,
                actionType: $filters['action_type'] ?? null,
                perPage: 1000,
            ));

            $rows = $report->getQuery()->limit(1000)->get();
            $columns = $report->getColumns();

            $pdf = Pdf::loadView('reports.pdf.table', [
                'title' => ucfirst(str_replace('_', ' ', $export->report_type->value)).' Report',
                'columns' => $columns,
                'rows' => $rows,
                'filters' => $filters,
                'generatedAt' => now()->toDateTimeString(),
            ]);

            $path = "reports/{$export->id}.pdf";
            Storage::disk('local')->put($path, $pdf->output());

            $export->update([
                'status' => ReportExportStatus::Completed,
                'file_path' => $path,
            ]);

            $admin = User::query()->findOrFail($this->requestedBy);

            $auditLogger->log(
                actor: $admin,
                actionType: AuditActionType::ReportExported,
                targetTable: 'report_exports',
                targetId: null,
                metadata: [
                    'export_id' => $export->id,
                    'report_type' => $export->report_type->value,
                    'filters' => $filters,
                ],
            );
        } catch (Throwable $exception) {
            $export->update(['status' => ReportExportStatus::Failed]);

            throw $exception;
        }
    }
}
