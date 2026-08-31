<?php

namespace App\Http\Controllers\Api\V1;

use App\Admin\DTOs\ReportFilterDto;
use App\Admin\Reports\ReportFactory;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminResourceController extends Controller
{
    public function __construct(private ReportFactory $reportFactory) {}

    public function users(Request $request): JsonResponse
    {
        return $this->paginatedResource($request, ReportType::Users);
    }

    public function donations(Request $request): JsonResponse
    {
        return $this->paginatedResource($request, ReportType::Donations);
    }

    public function requests(Request $request): JsonResponse
    {
        return $this->paginatedResource($request, ReportType::Requests);
    }

    public function matches(Request $request): JsonResponse
    {
        return $this->paginatedResource($request, ReportType::Matches);
    }

    public function pickups(Request $request): JsonResponse
    {
        return $this->paginatedResource($request, ReportType::Pickups);
    }

    private function paginatedResource(Request $request, ReportType $type): JsonResponse
    {
        $report = $this->reportFactory->make(new ReportFilterDto(
            type: $type,
            perPage: (int) $request->integer('per_page', 25),
        ));

        $paginator = $report->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
