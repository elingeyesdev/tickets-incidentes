<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Enums;

enum AlertType: string
{
    case SECURITY = 'security';
    case SYSTEM = 'system';
    case SERVICE = 'service';
    case COMPLIANCE = 'compliance';

    /**
     * Determine if this alert type is critical
     *
     * Critical alerts (SECURITY, COMPLIANCE) require immediate attention
     * and higher priority notification routing
     */
    public function isCritical(): bool
    {
        return match($this) {
            self::SECURITY, self::COMPLIANCE => true,
            self::SYSTEM, self::SERVICE => false,
        };
    }

    /**
     * Determine if this alert type is operational
     *
     * Operational alerts (SYSTEM, SERVICE) are important but not
     * as urgent as critical alerts
     */
    public function isOperational(): bool
    {
        return !$this->isCritical();
    }
}
