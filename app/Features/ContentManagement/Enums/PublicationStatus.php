<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Enums;

enum PublicationStatus: string
{
    case DRAFT = 'DRAFT';
    case SCHEDULED = 'SCHEDULED';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';
}
