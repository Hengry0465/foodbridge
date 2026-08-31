<?php

namespace App\Jobs;

use App\Admin\Services\PlatformStatsAggregator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AggregatePlatformStatsJob implements ShouldQueue
{
    use Queueable;

    public function handle(PlatformStatsAggregator $aggregator): void
    {
        $aggregator->aggregate();
    }
}
