<?php

namespace App\Services;

use App\Contracts\MatchObserver;
use App\Events\MatchOutcome;

class MatchPublisher
{
    /** @var array<string, MatchObserver> */
    private array $observers = [];

    public function attach(MatchObserver $observer): void
    {
        $this->observers[$observer::class] = $observer;
    }

    public function detach(MatchObserver $observer): void
    {
        unset($this->observers[$observer::class]);
    }

    public function notify(MatchOutcome $event): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($event);
        }
    }
}
