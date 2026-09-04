<?php

namespace App\Enums;

enum UserRole: string
{
    case Donor = 'donor';
    case Recipient = 'recipient';
    case Admin = 'admin';
}
