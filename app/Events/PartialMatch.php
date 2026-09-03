<?php

namespace App\Events;

final class PartialMatch extends MatchOutcome
{
    public function type(): string
    {
        return 'partial';
    }
}
