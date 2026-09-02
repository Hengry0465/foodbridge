<?php

namespace Tests\Unit;

use App\Contracts\MatchObserver;
use App\Events\MatchFailed;
use App\Events\MatchOutcome;
use App\Models\FoodRequest;
use App\Services\MatchPublisher;
use PHPUnit\Framework\TestCase;

class MatchPublisherTest extends TestCase
{
    public function test_observers_can_be_attached_notified_and_detached(): void
    {
        $observer = new class implements MatchObserver
        {
            public int $notifications = 0;

            public function update(MatchOutcome $event): void
            {
                $this->notifications++;
            }
        };
        $publisher = new MatchPublisher;
        $event = new MatchFailed(new FoodRequest);

        $publisher->attach($observer);
        $publisher->notify($event);
        $publisher->detach($observer);
        $publisher->notify($event);

        $this->assertSame(1, $observer->notifications);
    }
}
