<?php

namespace App\Enums;

enum AuditActionType: string
{
    case UserDeactivated = 'user.deactivated';
    case UserActivated = 'user.activated';
    case UserUpdated = 'user.updated';
    case ReportExported = 'report.exported';
    case StatsViewed = 'stats.viewed';
}
