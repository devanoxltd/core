<?php

declare(strict_types=1);

use Devanox\Core\Console\Commands\TenantCreateDatabaseCommand;
use Devanox\Core\Events\Tenant\DatabaseCreated;
use Devanox\Core\Models\Tenant;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function (): void {
    Tenant::query()->forceDelete();

    $this->tenant = Tenant::withoutEvents(fn () => Tenant::query()->forceCreate([
        'id' => 1,
        'user_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'test@example.com',
        'status' => 'active',
    ]));
});

afterEach(function (): void {
    if (config('database.default') === 'mysql' || config('database.connections.' . config('database.default') . '.driver') === 'mysql') {
        try {
            DB::statement('DROP DATABASE IF EXISTS `tenant_1`');
            DB::statement("DROP USER IF EXISTS 'tenant_user'@'%'");
        } catch (Throwable) {
            // Ignore mock errors
        }
    }
});

it('fails when tenant ID is not provided', function (): void {
    $status = Artisan::call('tenant:create-database', [
        'id' => null, // Although console handles missing required arguments, we can test logic
    ]);
    $output = Artisan::output();
    expect($status)->toBe(1)
        ->and($output)->toContain('Invalid tenant ID provided');
});
it('fails when invalid tenant ID array is provided', function (): void {
    $command = $this->app->make(TenantCreateDatabaseCommand::class);

    $input = Mockery::mock(InputInterface::class);
    $input->shouldReceive('getArgument')->with('id')->andReturn(['array']);
    $input->shouldReceive('bind', 'isInteractive', 'hasArgument', 'validate', 'getOptions', 'getArguments')->andReturn(true);
    $input->shouldReceive('hasOption')->andReturn(false);

    $output = Mockery::mock(OutputStyle::class)->makePartial();
    $output->shouldReceive('writeln')->andReturn();

    $command->setInput($input);
    $command->setOutput($output);

    $status = $command->handle();

    expect($status)->toBe(1);
});

it('fails when tenant is not found', function (): void {
    $status = Artisan::call('tenant:create-database', [
        'id' => 'missing-id',
    ]);

    $output = Artisan::output();

    expect($status)->toBe(1)
        ->and($output)->toContain('Tenant `missing-id` not found');
});

it('creates database for tenant with root user', function (): void {
    $this->withoutExceptionHandling();
    Event::fake();
    config(['tenancy.database.prefix' => 'tenant_']);
    config(['database.connections.mysql.username' => 'root']); // root user skips permissions

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('selectOne')
        ->andReturnUsing(fn ($query, $bindings = []): null => null);
    $db->shouldReceive('statement')->andReturn(true);
    DB::swap($db);

    $status = Artisan::call('tenant:create-database', [
        'id' => 1,
    ]);

    $output = Artisan::output();

    expect($status)->toBe(0)
        ->and($output)->toContain('Database `tenant_1` has been created successfully')
        ->and($output)->toContain('Tenant database configuration saved successfully');

    $tenant = Tenant::query()->find(1);

    expect($tenant->config)->toBeInstanceOf(Collection::class)
        ->and($tenant->config['database']['database'])->toBe('tenant_1');

    Event::assertDispatched(DatabaseCreated::class);

    DB::clearResolvedInstance('db');
});

it('creates database and user when username is not root', function (): void {
    Event::fake();
    config(['tenancy.database.prefix' => 'tenant_']);
    config(['database.connections.mysql.username' => 'tenant_user']);
    config(['database.connections.mysql.password' => 'secret']);

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('selectOne')
        ->andReturnUsing(fn ($query, $bindings = []): null => null);
    $db->shouldReceive('statement')->andReturn(true);
    DB::swap($db);

    $status = Artisan::call('tenant:create-database', [
        'id' => 1,
    ]);

    $output = Artisan::output();

    expect($status)->toBe(0)
        ->and($output)->toContain('Database `tenant_1` has been created successfully')
        ->and($output)->toContain('Database user `tenant_user` has been created successfully')
        ->and($output)->toContain('Permissions granted to database `tenant_1`')
        ->and($output)->toContain('Database privileges flushed successfully')
        ->and($output)->toContain('Tenant database configuration saved successfully');

    Event::assertDispatched(DatabaseCreated::class);

    DB::clearResolvedInstance('db');
});

it('handles existing user and increments database name if exists', function (): void {
    Event::fake();
    config(['tenancy.database.prefix' => 'tenant_']);
    config(['database.connections.mysql.username' => 'tenant_user']);
    config(['database.connections.mysql.password' => '']); // empty password

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('selectOne')
        ->andReturnUsing(function ($query, $bindings = []) {
            if (str_contains($query, 'INFORMATION_SCHEMA.SCHEMATA')) {
                return isset($bindings[0]) && $bindings[0] === 'tenant_1'
                    ? (object) ['SCHEMA_NAME' => 'tenant_1']
                    : null;
            }

            if (str_contains($query, 'mysql.user')) {
                return (object) ['User' => 'tenant_user'];
            }

            return null;
        });
    $db->shouldReceive('statement')->andReturn(true);
    DB::swap($db);

    $status = Artisan::call('tenant:create-database', [
        'id' => 1,
    ]);

    $output = Artisan::output();

    expect($status)->toBe(0)
        ->and($output)->toContain('Database `tenant_1_1` has been created successfully')
        ->and($output)->toContain('Permissions granted to database `tenant_1_1`');
});

it('creates database and user with null password', function (): void {
    Event::fake();
    config(['tenancy.database.prefix' => 'tenant_']);
    config(['database.connections.mysql.username' => 'tenant_user']);
    config(['database.connections.mysql.password' => null]); // null password

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('selectOne')
        ->andReturnUsing(fn ($query, $bindings = []): null => null);
    $db->shouldReceive('statement')->andReturn(true);
    DB::swap($db);

    $status = Artisan::call('tenant:create-database', [
        'id' => 1,
    ]);

    $output = Artisan::output();

    expect($status)->toBe(0)
        ->and($output)->toContain('Database `tenant_1` has been created successfully');

    DB::clearResolvedInstance('db');
});
