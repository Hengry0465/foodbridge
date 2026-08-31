<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Available = 'available';
    case Matched = 'matched';
    case Completed = 'completed';
    case Expired = 'expired';
}
