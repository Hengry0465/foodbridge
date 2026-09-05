<?php
namespace App\Http\Controllers\Api\V1;
use App\Admin\DTOs\ReportFilterDto;
use App\Enums\ReportExportStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportReportRequest;
use App\Jobs\ExportReportPdfJob;
use App\Models\ReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function store(ExportReportRequest $request): JsonResponse
    {
        $filters = $this->buildFilters($request);

        $export = ReportExport::query()->create([
            'requested_by' => $request->user()->id,
            'report_type' => $filters->type,
            'filters' => $filters->toArray(),
            'status' => ReportExportStatus::Pending,
        ]);

        ExportReportPdfJob::dispatch($export->id, $request->user()->id);

        return response()->json([
            'message' => 'Export queued.',
            'data' => [
                'export_id' => $export->id,
                'status' => $export->status->value,
            ],
        ], Response::HTTP_ACCEPTED);
    }

    public function show(ReportExport $export): JsonResponse
    {
        abort_unless($export->requested_by === request()->user()->id, Response::HTTP_FORBIDDEN);

        return response()->json([
            'data' => [
                'export_id' => $export->id,
                'status' => $export->status->value,
                'download_url' => $export->status === ReportExportStatus::Completed
                    ? route('api.v1.reports.exports.download', $export)
                    : null,
            ],
        ]);
    }

    public function download(ReportExport $export): StreamedResponse
    {
        abort_unless($export->requested_by === request()->user()->id, Response::HTTP_FORBIDDEN);
        abort_unless(
            $export->status === ReportExportStatus::Completed && $export->file_path !== null,
            Response::HTTP_NOT_FOUND,
        );

        return Storage::disk('local')->download(
            $export->file_path,
            "foodbridge-{$export->report_type->value}-report.pdf",
        );
    }

    private function buildFilters(ExportReportRequest $request): ReportFilterDto
    {
        $validated = $request->validated();

        return new ReportFilterDto(
            type: ReportType::from($validated['type']),
            from: isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null,
            to: isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null,
            status: $validated['status'] ?? null,
            category: isset($validated['category']) ? (string) $validated['category'] : null,
            role: isset($validated['role']) ? (string) $validated['role'] : null,
            isActive: array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
            actorId: isset($validated['actor_id']) ? (int) $validated['actor_id'] : null,
            actionType: $validated['action_type'] ?? null,
            perPage: 1000,
        );
    }
}