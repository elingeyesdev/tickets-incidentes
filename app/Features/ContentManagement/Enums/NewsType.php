<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Enums;

enum NewsType: string
{
    case FEATURE_RELEASE = 'feature_release';
    case POLICY_UPDATE = 'policy_update';
    case GENERAL_UPDATE = 'general_update';
}
