<?php

declare(strict_types=1);

use Devanox\Core\Events\Domain\Created as DomainCreated;
use Devanox\Core\Events\Domain\Creating as DomainCreating;
use Devanox\Core\Events\Domain\Deleted as DomainDeleted;
use Devanox\Core\Events\Domain\Deleting as DomainDeleting;
use Devanox\Core\Events\Domain\Saved as DomainSaved;
use Devanox\Core\Events\Domain\Saving as DomainSaving;
use Devanox\Core\Events\Domain\Updated as DomainUpdated;
use Devanox\Core\Events\Domain\Updating as DomainUpdating;
use Devanox\Core\Events\Tenant\Created as TenantCreated;
use Devanox\Core\Events\Tenant\Creating as TenantCreating;
use Devanox\Core\Events\Tenant\DatabaseCreated as TenantDatabaseCreated;
use Devanox\Core\Events\Tenant\Deleted as TenantDeleted;
use Devanox\Core\Events\Tenant\Deleting as TenantDeleting;
use Devanox\Core\Events\Tenant\Saved as TenantSaved;
use Devanox\Core\Events\Tenant\Saving as TenantSaving;
use Devanox\Core\Events\Tenant\Updated as TenantUpdated;
use Devanox\Core\Events\Tenant\Updating as TenantUpdating;
use Devanox\Core\Models\Domain;
use Devanox\Core\Models\Tenant;

it('instantiates domain events', function (): void {
    $domain = new Domain;

    $events = [
        new DomainCreated($domain),
        new DomainCreating($domain),
        new DomainDeleted($domain),
        new DomainDeleting($domain),
        new DomainSaved($domain),
        new DomainSaving($domain),
        new DomainUpdated($domain),
        new DomainUpdating($domain),
    ];

    foreach ($events as $event) {
        expect($event->domain)->toBe($domain);
    }
});

it('instantiates tenant events', function (): void {
    $tenant = new Tenant;

    $events = [
        new TenantCreated($tenant),
        new TenantCreating($tenant),
        new TenantDatabaseCreated($tenant),
        new TenantDeleted($tenant),
        new TenantDeleting($tenant),
        new TenantSaved($tenant),
        new TenantSaving($tenant),
        new TenantUpdated($tenant),
        new TenantUpdating($tenant),
    ];

    foreach ($events as $event) {
        expect($event->tenant)->toBe($tenant);
    }
});
