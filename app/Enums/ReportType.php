<?php

namespace App\Enums;

enum ReportType: string
{
    case Users = 'users';
    case Donations = 'donations';
    case Requests = 'requests';
    case Matches = 'matches';
    case Pickups = 'pickups';
    case AuditLogs = 'audit_logs';
}
