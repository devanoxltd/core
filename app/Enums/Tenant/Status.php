<?php

declare(strict_types=1);

namespace Devanox\Core\Enums\Tenant;

enum Status: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('core::tenancy.enum.tenant.status.label.active'),
            self::Suspended => __('core::tenancy.enum.tenant.status.label.suspended'),
        };
    }
}
