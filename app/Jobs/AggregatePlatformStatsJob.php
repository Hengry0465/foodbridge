<?php

namespace App\Jobs;

use App\Admin\Services\PlatformStatsAggregator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class AggregatePlatformStatsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(PlatformStatsAggregator $aggregator): void
    {
        $aggregator->aggregate();
    }
}
