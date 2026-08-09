<?php

declare(strict_types=1);

use Devanox\Core\Events\Tenant\Created as TenantCreatedEvent;
use Devanox\Core\Events\Tenant\DatabaseCreated as TenantDatabaseCreatedEvent;
use Devanox\Core\Listeners\Tenant\Created;
use Devanox\Core\Listeners\Tenant\DatabaseCreated;
use Devanox\Core\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

it('handles TenantCreated event and creates database', function (): void {
    $tenant = new Tenant;
    $tenant->id = 'test_tenant';

    $event = new TenantCreatedEvent($tenant);
    $listener = new Created;

    $called = false;
    Artisan::command('tenant:create-database {id}', function () use (&$called): int {
        $called = true;

        return 0;
    });

    $listener->handle($event);

    expect($called)->toBeTrue();
});

it('handles TenantDatabaseCreated event and runs migrations', function (): void {
    $tenant = new Tenant;
    $tenant->id = 'test_tenant';

    $event = new TenantDatabaseCreatedEvent($tenant);
    $listener = new DatabaseCreated;

    $migrateCalled = false;
    $moduleMigrateCalled = false;

    Artisan::command('tenant {artisanCommand} {--tenant=}', function () use (&$migrateCalled, &$moduleMigrateCalled): int {
        if ($this->argument('artisanCommand') === 'migrate --path=database/migrations/tenant --force') {
            $migrateCalled = true;
        } elseif ($this->argument('artisanCommand') === 'module:migrate') {
            $moduleMigrateCalled = true;
        }

        return 0;
    });

    $listener->handle($event);

    expect($migrateCalled)->toBeTrue()
        ->and($moduleMigrateCalled)->toBeTrue();
});
