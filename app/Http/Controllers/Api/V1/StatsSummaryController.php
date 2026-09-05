<?php
namespace App\Http\Controllers\Api\V1;
use App\Admin\Services\AuditLogger;
use App\Enums\AuditActionType;
use App\Http\Controllers\Controller;
use App\Models\PlatformStat;
use Illuminate\Http\JsonResponse;

class StatsSummaryController extends Controller
{
    public function __invoke(AuditLogger $auditLogger): JsonResponse
    {
        $auditLogger->log(
            actor: request()->user(),
            actionType: AuditActionType::StatsViewed,
            metadata: ['period' => request('period', 'latest')],
        );

        $period = request('period', 'latest');
        $stat = $this->resolvePlatformStat($period);

        if ($stat === null) {
            return response()->json([
                'message' => 'No platform statistics available yet.',
                'data' => null,
            ], JsonResponse::HTTP_OK);
        }

        return response()->json([
            'data' => [
                'generated_at' => $stat->created_at ? \Carbon\Carbon::parse($stat->created_at)->toIso8601String() : null,
                'period_start' => \Carbon\Carbon::parse($stat->period_start)->toIso8601String(),
                'period_end' => \Carbon\Carbon::parse($stat->period_end)->toIso8601String(),
                'metrics' => $stat->metrics,
            ],
        ]);
    }

    private function resolvePlatformStat(string $period): ?PlatformStat
    {
        if ($period === 'latest') {
            return PlatformStat::query()->latest('period_start')->first();
        }

        $since = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => null,
        };

        if ($since === null) {
            return PlatformStat::query()->latest('period_start')->first();
        }

        return PlatformStat::query()
            ->where('period_start', '>=', $since)
            ->latest('period_start')
            ->first();
    }
}