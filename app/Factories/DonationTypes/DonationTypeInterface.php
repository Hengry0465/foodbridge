<?php
namespace App\Factories\DonationTypes;

use Carbon\Carbon;

interface DonationTypeInterface
{
    public function calculateExpiry(?Carbon $providedExpiry = null): Carbon;

    public function label(): string;
}