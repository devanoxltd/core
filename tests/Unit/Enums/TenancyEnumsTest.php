<?php

declare(strict_types=1);

use Devanox\Core\Enums\Domain\Status as DomainStatus;
use Devanox\Core\Enums\Domain\Type as DomainType;
use Devanox\Core\Enums\Tenant\Status as TenantStatus;

it('tests DomainStatus enum labels', function (): void {
    expect(DomainStatus::Pending->label())->toBe(__('core::tenancy.enum.domain.status.label.pending'))
        ->and(DomainStatus::Verified->label())->toBe(__('core::tenancy.enum.domain.status.label.verified'))
        ->and(DomainStatus::Approval->label())->toBe(__('core::tenancy.enum.domain.status.label.approval'))
        ->and(DomainStatus::Rejected->label())->toBe(__('core::tenancy.enum.domain.status.label.rejected'))
        ->and(DomainStatus::Active->label())->toBe(__('core::tenancy.enum.domain.status.label.active'))
        ->and(DomainStatus::Inactive->label())->toBe(__('core::tenancy.enum.domain.status.label.inactive'));
});

it('tests DomainType enum methods', function (): void {
    expect(DomainType::Domain->label())->toBe(__('core::tenancy.enum.domain.type.label.domain'))
        ->and(DomainType::Subdomain->label())->toBe(__('core::tenancy.enum.domain.type.label.subdomain'))
        ->and(DomainType::Domain->description())->toBe(__('core::tenancy.enum.domain.type.description.domain'))
        ->and(DomainType::Subdomain->description())->toBe(__('core::tenancy.enum.domain.type.description.subdomain'))
        ->and(DomainType::Domain->is(DomainType::Domain))->toBeTrue()
        ->and(DomainType::Subdomain->is(DomainType::Domain))->toBeFalse();
});

it('tests TenantStatus enum labels', function (): void {
    expect(TenantStatus::Active->label())->toBe(__('core::tenancy.enum.tenant.status.label.active'))
        ->and(TenantStatus::Suspended->label())->toBe(__('core::tenancy.enum.tenant.status.label.suspended'));
});
