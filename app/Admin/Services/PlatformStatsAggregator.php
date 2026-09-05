<?php
namespace App\Admin\Services;
use App\Models\Donation;
use App\Models\MatchRecord;
use App\Models\FoodRequest;
use App\Models\Pickup;
use App\Models\PlatformStat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PlatformStatsAggregator
{
    /**
     * @return array<string, mixed>
     */
    public function aggregate(?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $periodStart ??= now()->startOfHour()->subHour();
        $periodEnd ??= now()->startOfHour();

        $metrics = [
            'users' => [
                'total' => User::query()->count(),
                'donors' => User::query()->where('role', 'donor')->count(),
                'recipients' => User::query()->where('role', 'recipient')->count(),
                'admins' => User::query()->where('role', 'admin')->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
            'donations' => [
                'total' => Donation::query()->count(),
                'available' => Donation::query()->where('status', 'available')->count(),
                'expiring_soon' => Donation::query()->where('status', 'expiring_soon')->count(),
                'reserved' => Donation::query()->where('status', 'reserved')->count(),
                'completed' => Donation::query()->where('status', 'completed')->count(),
                'expired' => Donation::query()->where('status', 'expired')->count(),
                'cancelled' => Donation::query()->where('status', 'cancelled')->count(),
            ],
            'requests' => [
                'total' => FoodRequest::query()->count(),
                'pending' => FoodRequest::query()->where('status', 'pending')->count(),
                'matched' => FoodRequest::query()->where('status', 'matched')->count(),
                'partial' => FoodRequest::query()->where('status', 'partial')->count(),
                'withdrawn' => FoodRequest::query()->where('status', 'withdrawn')->count(),
            ],
            'matches' => [
                'total' => MatchRecord::query()->count(),
                'confirmed' => MatchRecord::query()->where('status', 'confirmed')->count(),
            ],
            'pickups' => [
                'total' => Pickup::query()->count(),
                'scheduled' => Pickup::query()->whereHas('status', fn (Builder $q) => $q->where('code', 'scheduled'))->count(),
                'confirmed' => Pickup::query()->whereHas('status', fn (Builder $q) => $q->where('code', 'confirmed'))->count(),
                'completed' => Pickup::query()->whereHas('status', fn (Builder $q) => $q->where('code', 'completed'))->count(),
                'cancelled' => Pickup::query()->whereHas('status', fn (Builder $q) => $q->where('code', 'cancelled'))->count(),
                'expired_pickup' => Pickup::query()->whereHas('status', fn (Builder $q) => $q->where('code', 'expired_pickup'))->count(),
            ],
            'impact' => [
                'meals_redistributed' => (int) Donation::query()->where('status', 'completed')->sum('quantity'),
                'food_kg_saved' => (float) Donation::query()->where('status', 'completed')->sum('quantity'),
            ],
        ];

        PlatformStat::query()->create([
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'metrics' => json_encode($metrics),
            'created_at' => now(),
        ]);

        return $metrics;
    }
}