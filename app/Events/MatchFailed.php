<?php

namespace App\Events;

final class MatchFailed extends MatchOutcome
{
    public function type(): string
    {
        return 'pending';
    }
}
