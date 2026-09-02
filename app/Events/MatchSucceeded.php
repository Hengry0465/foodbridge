<?php

namespace App\Events;

final class MatchSucceeded extends MatchOutcome
{
    public function type(): string
    {
        return 'matched';
    }
}
