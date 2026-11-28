<?php

declare(strict_types=1);

use Devanox\Core\Models\Tenant;
use Devanox\Core\Providers\TenantQueueServiceProvider;
use Devanox\Core\Support\Tenancy;
use Devanox\Core\Support\TenantDatabaseContext;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use function Devanox\Core\Helpers\tenancy;

it('registers the tenant queue worker command', function (): void {
    expect(Artisan::all())->toHaveKey('tenant:queue-work');
});

it('enables tenant queue database contexts whenever tenancy is enabled', function (): void {
    expect(app()->getProvider(TenantQueueServiceProvider::class))->not->toBeNull();
});

it('leaves queue repositories unchanged when tenancy is disabled', function (): void {
    config(['tenancy.enabled' => false]);
    $bindings = app()->getBindings();

    new TenantQueueServiceProvider(app())->boot();

    expect(app()->getBindings()[BatchRepository::class])->toBe($bindings[BatchRepository::class])
        ->and(app()->getBindings()['queue.failer'])->toBe($bindings['queue.failer']);
});

it('resolves central and tenant queue repositories dynamically', function (): void {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Tenant',
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => ['database' => ['driver' => 'sqlite', 'database' => ':memory:']],
    ]));

    expect(resolve(BatchRepository::class))->toBeInstanceOf(DatabaseBatchRepository::class)
        ->and(resolve('queue.failer'))->toBeInstanceOf(DatabaseUuidFailedJobProvider::class);

    tenancy()->run($tenant, function () use ($tenant): void {
        $batchRepository = resolve(BatchRepository::class);
        $failer = resolve('queue.failer');

        expect($batchRepository)->toBeInstanceOf(DatabaseBatchRepository::class)
            ->and($batchRepository->getConnection()->getName())->toBe('mysql_tenant_' . $tenant->getKey())
            ->and($failer)->toBeInstanceOf(DatabaseUuidFailedJobProvider::class);
    });

    Config::set('queue.failed.driver', 'database');
    new TenantQueueServiceProvider(app())->boot();
    expect(resolve('queue.failer'))->toBeInstanceOf(DatabaseFailedJobProvider::class);

    Config::set('queue.failed.database');
    expect(resolve('queue.failer'))->toBeInstanceOf(DatabaseFailedJobProvider::class);

    Config::set('queue.failed.database', '');
    expect(fn (): mixed => resolve('queue.failer'))
        ->toThrow(RuntimeException::class, 'Failed job database connection is required.');

    Config::set('queue.batching.database', 123);
    expect(fn (): mixed => resolve(BatchRepository::class))
        ->toThrow(RuntimeException::class, 'Invalid batch database connection.');
});

it('runs with the configured tenant database and restores the central context', function (): void {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Tenant',
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => ['database' => ['driver' => 'sqlite', 'database' => ':memory:']],
    ]));
    $context = resolve(TenantDatabaseContext::class);
    $central = config('database.default');

    $connectionName = $context->run((string) $tenant->getKey(), fn (): string => $context->connection((string) $tenant->getKey())->getName());

    expect($connectionName)->toBe('mysql_tenant_' . $tenant->getKey())
        ->and(tenancy()->tenant)->toBeNull()
        ->and(config('database.default'))->toBe($central);
});

it('runs central callbacks without clobbering central defaults and restores active tenant context', function (): void {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Tenant',
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => ['database' => ['driver' => 'sqlite', 'database' => ':memory:']],
    ]));
    $context = resolve(TenantDatabaseContext::class);

    Config::set('cache.default', 'array');

    $centralCacheDriver = $context->run(null, fn (): mixed => config('cache.default'));

    expect($centralCacheDriver)->toBe('array')
        ->and($context->connection(null)->getName())->toBe('sqlite');

    tenancy()->run($tenant, function () use ($context, $tenant): void {
        $innerConnection = $context->run(null, fn (): string => $context->connection(null)->getName());

        expect($innerConnection)->toBe('sqlite')
            ->and(tenancy()->tenant?->getKey())->toBe($tenant->getKey())
            ->and(config('database.default'))->toBe('mysql_tenant_' . $tenant->getKey());
    });
});

it('validates context run inputs, connection config, and central connection fallbacks', function (): void {
    $context = resolve(TenantDatabaseContext::class);

    expect(fn (): mixed => $context->run('', fn (): null => null))
        ->toThrow(RuntimeException::class, 'Tenant context is unavailable.');

    Config::set('tenancy.models.tenant', stdClass::class);
    expect(fn (): mixed => $context->run('1', fn (): null => null))
        ->toThrow(RuntimeException::class, 'Invalid tenant model configuration.');

    $savedTenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Central Check Tenant',
        'email' => 'central@example.com',
        'status' => 'active',
        'config' => ['database' => ['driver' => 'sqlite', 'database' => ':memory:']],
    ]));

    $unsavedTenant = new Tenant;
    $tenancy = new Tenancy;
    $tenancy->tenant = $unsavedTenant;

    expect(fn (): ?string => $context->activeTenantId($tenancy))
        ->toThrow(RuntimeException::class, 'Invalid tenant identifier.');

    tenancy()->run($savedTenant, function () use ($context, $savedTenant): void {
        Config::set('tenancy.database.central_connection', '');

        expect(fn (): Connection => $context->connection((string) $savedTenant->getKey()))
            ->toThrow(RuntimeException::class, 'Tenant database context could not be initialized.');
    });

    Config::set('tenancy.database.central_connection', '');
    expect($context->connection(null)->getName())->toBe('sqlite');

    Config::set('database.default', '');

    try {
        expect(fn (): Connection => $context->connection(null))
            ->toThrow(RuntimeException::class, 'Tenant database context could not be initialized.');
    } finally {
        Config::set('database.default', 'sqlite');
        Config::set('tenancy.database.central_connection', 'sqlite');
    }
});

it('rejects connections whose resolved config or database name does not match', function (): void {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Tenant',
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => ['database' => ['driver' => 'sqlite', 'database' => ':memory:']],
    ]));
    $connectionName = 'mysql_tenant_' . $tenant->getKey();
    $context = resolve(TenantDatabaseContext::class);
    $originalDb = DB::getFacadeRoot();

    try {
        $nonArrayConnection = Mockery::mock(Connection::class);
        $nonArrayConnection->shouldReceive('getConfig')->andReturn('invalid');

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('connection')->with('sqlite')->andReturn($originalDb->connection('sqlite'));
        $db->shouldReceive('connection')->with($connectionName)->andReturn($nonArrayConnection);
        DB::swap($db);

        tenancy()->tenant = $tenant;
        Config::set('database.default', $connectionName);
        Config::set('tenancy.current', $tenant);
        Config::set('database.connections.' . $connectionName, ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);

        expect(fn (): Connection => $context->connection((string) $tenant->getKey()))
            ->toThrow(RuntimeException::class, 'Tenant database context could not be initialized.');

        $wrongDbConnection = Mockery::mock(Connection::class);
        $wrongDbConnection->shouldReceive('getConfig')->andReturn(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $wrongDbConnection->shouldReceive('getDatabaseName')->andReturn('other_db');

        $dbWrong = Mockery::mock(DatabaseManager::class);
        $dbWrong->shouldReceive('connection')->with('sqlite')->andReturn($originalDb->connection('sqlite'));
        $dbWrong->shouldReceive('connection')->with($connectionName)->andReturn($wrongDbConnection);
        DB::swap($dbWrong);

        expect(fn (): Connection => $context->connection((string) $tenant->getKey()))
            ->toThrow(RuntimeException::class, 'Tenant database context could not be initialized.');
    } finally {
        DB::swap($originalDb);
        tenancy()->unsetTenant();
    }

    tenancy()->run($tenant, function () use ($context, $connectionName, $tenant): void {
        Config::set("database.connections.{$connectionName}.driver", 'mysql');

        expect(fn (): Connection => $context->connection((string) $tenant->getKey()))
            ->toThrow(RuntimeException::class, 'Tenant database context could not be initialized.');
    });
});

it('detects central database collisions across normalized port and unix socket values', function (): void {
    $context = resolve(TenantDatabaseContext::class);

    $central = [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'unix_socket' => '',
        'database' => 'app_central',
    ];

    expect($context->matchesCentralDatabase([
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'unix_socket' => null,
        'database' => 'app_central',
    ], $central))->toBeTrue()
        ->and($context->matchesCentralDatabase([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'unix_socket' => null,
            'database' => 'tenant_1',
        ], $central))->toBeFalse();
});

it('fails closed and restores context when tenant database configuration is missing', function (): void {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Tenant',
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => [],
    ]));
    $central = config('database.default');

    expect(fn (): mixed => resolve(TenantDatabaseContext::class)->run((string) $tenant->getKey(), fn (): null => null))
        ->toThrow(RuntimeException::class, 'Tenant database context');

    expect(tenancy()->tenant)->toBeNull()
        ->and(config('database.default'))->toBe($central);
});

it('rejects a database request for a tenant other than the active tenant', function (): void {
    expect(fn (): Connection => resolve(TenantDatabaseContext::class)->connection('1'))
        ->toThrow(RuntimeException::class, 'does not match');
});

it('does not execute tenant callbacks when the tenant database is missing', function (): void {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Tenant',
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => [],
    ]));
    $executed = false;
    $central = config('database.default');

    expect(fn (): mixed => tenancy()->run($tenant, function () use (&$executed): void {
        $executed = true;
    }))->toThrow(Exception::class);

    expect($executed)->toBeFalse()
        ->and(tenancy()->tenant)->toBeNull()
        ->and(config('database.default'))->toBe($central);
});

it('rejects tenant callbacks when tenancy is disabled', function (): void {
    $tenant = new Tenant;
    $executed = false;
    config(['tenancy.enabled' => false]);

    expect(fn (): mixed => tenancy()->run($tenant, function () use (&$executed): void {
        $executed = true;
    }))->toThrow(RuntimeException::class)
        ->and(fn (): string => tenancy()->setTenant($tenant))->toThrow(RuntimeException::class)
        ->and(fn (): ?string => tenancy()->setTenantConfig())->toThrow(RuntimeException::class);

    expect($executed)->toBeFalse();
});

it('rejects a tenant configured to use the central database and rolls back initializeTenant', function (): void {
    $database = '/tenant-context-central.sqlite';
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Tenant',
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => ['database' => ['driver' => 'sqlite', 'database' => $database]],
    ]));
    $tenant->setRelation('domains', new Collection);
    config([
        'database.connections.central_collision' => ['driver' => 'sqlite', 'database' => $database, 'prefix' => ''],
        'tenancy.database.central_connection' => 'central_collision',
    ]);
    $central = config('database.default');

    expect(fn (): string => tenancy()->setTenant($tenant))->toThrow(RuntimeException::class, 'Tenant database context')
        ->and(tenancy()->tenant)->toBeNull()
        ->and(config('database.default'))->toBe($central);

    $tenancy = new Tenancy;
    $tenancy->tenant = $tenant;

    expect(fn () => $tenancy->initializeTenant())->toThrow(RuntimeException::class, 'Tenant database context')
        ->and(config('database.default'))->toBe($central);
});
