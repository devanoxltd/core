<?php

declare(strict_types=1);

namespace Devanox\Core\Enums\Domain;

enum Status: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Approval = 'approval';
    case Rejected = 'rejected';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('core::tenancy.enum.domain.status.label.pending'),
            self::Verified => __('core::tenancy.enum.domain.status.label.verified'),
            self::Approval => __('core::tenancy.enum.domain.status.label.approval'),
            self::Rejected => __('core::tenancy.enum.domain.status.label.rejected'),
            self::Active => __('core::tenancy.enum.domain.status.label.active'),
            self::Inactive => __('core::tenancy.enum.domain.status.label.inactive'),
        };
    }
}
