<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Enums;

enum UrgencyLevel: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';

    case CRITICAL = 'CRITICAL';
}
