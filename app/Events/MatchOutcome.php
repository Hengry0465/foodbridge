<?php

namespace App\Events;

use App\Models\FoodRequest;

abstract class MatchOutcome
{
    public function __construct(public FoodRequest $foodRequest) {}

    abstract public function type(): string;
}
