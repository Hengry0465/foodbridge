<?php

namespace App\Contracts;

use App\Events\MatchOutcome;

interface MatchObserver
{
    public function update(MatchOutcome $event): void;
}
