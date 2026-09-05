<?php
namespace App\Http\Controllers\Api\V1;
use App\Admin\DTOs\ReportFilterDto;
use App\Admin\Reports\ReportFactory;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __invoke(IndexReportRequest $request, ReportFactory $reportFactory): JsonResponse
    {
        $filters = $this->buildFilters($request);
        $report = $reportFactory->make($filters);
        $paginator = $report->paginate($filters->perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters_applied' => $filters->toArray(),
        ]);
    }

    private function buildFilters(IndexReportRequest $request): ReportFilterDto
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
            perPage: (int) ($validated['per_page'] ?? 25),
        );
    }
}