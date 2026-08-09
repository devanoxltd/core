<?php

declare(strict_types=1);

namespace Devanox\Core\Enums\Domain;

enum Type: string
{
    case Domain = 'domain';
    case Subdomain = 'subdomain';

    public function description(): string
    {
        return match ($this) {
            self::Domain => __('core::tenancy.enum.domain.type.description.domain'),
            self::Subdomain => __('core::tenancy.enum.domain.type.description.subdomain'),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Domain => __('core::tenancy.enum.domain.type.label.domain'),
            self::Subdomain => __('core::tenancy.enum.domain.type.label.subdomain'),
        };
    }

    public function is(Type $type): bool
    {
        return $this->value === $type->value;
    }
}
