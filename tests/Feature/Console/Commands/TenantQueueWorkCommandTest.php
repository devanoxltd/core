<?php

declare(strict_types=1);

use Devanox\Core\Models\Tenant;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Devanox\Core\Helpers\tenancy;

final class CoreTenantQueueJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(public bool $shouldFail = false) {}

    public function handle(): void
    {
        throw_if($this->shouldFail, RuntimeException::class, 'Expected worker failure.');

        DB::table('worker_results')->insert(['tenant_id' => tenancy()->tenant?->getKey()]);
    }
}

#[Table(name: 'tenants')]
final class InvalidKeyWorkTenant extends Tenant
{
    public function getKey(): mixed
    {
        return null;
    }
}

function workTestTenant(array $config = []): Tenant
{
    return Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => 'Worker Tenant',
        'email' => 'worker@example.com',
        'status' => 'active',
        'config' => $config,
    ]));
}

function createTenantQueueTables(string $connection): void
{
    $schema = Schema::connection($connection);

    $schema->create('jobs', function ($table): void {
        $table->bigIncrements('id');
        $table->string('queue')->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });

    $schema->create('job_batches', function ($table): void {
        $table->string('id')->primary();
        $table->string('name');
        $table->integer('total_jobs');
        $table->integer('pending_jobs');
        $table->integer('failed_jobs');
        $table->longText('failed_job_ids');
        $table->mediumText('options')->nullable();
        $table->integer('cancelled_at')->nullable();
        $table->integer('created_at');
        $table->integer('finished_at')->nullable();
    });

    $schema->create('failed_jobs', function ($table): void {
        $table->id();
        $table->string('uuid')->unique();
        $table->text('connection');
        $table->text('queue');
        $table->longText('payload');
        $table->longText('exception');
        $table->timestamp('failed_at')->useCurrent();
    });

    $schema->create('cache', function ($table): void {
        $table->string('key')->primary();
        $table->mediumText('value');
        $table->integer('expiration');
    });

    $schema->create('worker_results', function ($table): void {
        $table->unsignedBigInteger('tenant_id');
    });
}

it('fails closed on invalid state, tenant, or options', function (): void {
    Config::set('tenancy.enabled', false);
    expect(Artisan::call('tenant:queue-work', ['tenant' => '1', '--once' => true]))->toBe(1);

    Config::set('tenancy.enabled', true);
    expect(Artisan::call('tenant:queue-work', ['tenant' => '   ', '--once' => true]))->toBe(1);

    Config::set('tenancy.models.tenant', stdClass::class);
    expect(Artisan::call('tenant:queue-work', ['tenant' => '1', '--once' => true]))->toBe(1);

    Config::set('tenancy.models.tenant', Tenant::class);
    expect(Artisan::call('tenant:queue-work', ['tenant' => '999999', '--once' => true]))->toBe(1);

    $missingDbTenant = workTestTenant(['database' => ['host' => 'localhost']]);
    expect(Artisan::call('tenant:queue-work', ['tenant' => (string) $missingDbTenant->getKey(), '--once' => true]))->toBe(1);

    $suspended = workTestTenant(['database' => ['driver' => 'sqlite', 'database' => ':memory:']]);
    $suspended->updateQuietly(['status' => 'suspended']);

    expect(Artisan::call('tenant:queue-work', ['tenant' => (string) $suspended->getKey(), '--once' => true]))->toBe(1);

    Config::set('tenancy.models.tenant', InvalidKeyWorkTenant::class);
    $active = workTestTenant(['database' => ['driver' => 'sqlite', 'database' => ':memory:']]);
    expect(Artisan::call('tenant:queue-work', ['tenant' => (string) $active->getKey(), '--once' => true]))->toBe(1);
});

it('rejects invalid queue, timeout, batch, failed-job, and missing table configurations', function (): void {
    $tenant = workTestTenant(['database' => ['driver' => 'sqlite', 'database' => ':memory:']]);
    $tenantId = (string) $tenant->getKey();

    Config::set('queue.connections.database.driver', 'sync');
    expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1);

    Config::set('queue.connections.database.driver', 'database');
    Config::set('queue.connections.database.retry_after', 60);
    expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true, '--timeout' => 60]))->toBe(1);

    Config::set('queue.connections.database.retry_after', 90);
    Config::set('queue.batching.driver', 'dynamodb');
    expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1);

    Config::set('queue.batching.driver', 'database');
    Config::set('queue.failed.driver', 'null');
    expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1);

    Config::set('queue.failed.driver', 'database-uuids');
    expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('table is missing');

    Config::set('tenancy.database.central_connection', '');
    expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('Invalid tenant database');
    Config::set('tenancy.database.central_connection', 'sqlite');
});

it('rejects non-isolated connections and non-database queue implementations', function (): void {
    $path = __DIR__ . '/../../../temp/non-isolated-' . Str::random(12) . '.sqlite';
    touch($path);
    $tenant = workTestTenant(['database' => ['driver' => 'sqlite', 'database' => $path, 'prefix' => '']]);
    $tenantId = (string) $tenant->getKey();
    $connectionName = 'mysql_tenant_' . $tenantId;
    $queueConnection = 'database_tenant_' . $tenantId;
    $originalDb = DB::getFacadeRoot();
    $originalQueue = Queue::getFacadeRoot();
    $expectedConfig = array_merge(config('database.connections.mysql', []), ['driver' => 'sqlite', 'database' => $path, 'prefix' => '']);

    try {
        $mismatchConnection = Mockery::mock(Connection::class);
        $mismatchConnection->shouldReceive('getConfig')->andReturn($expectedConfig);
        $mismatchConnection->shouldReceive('getPdo')->andReturn(new PDO('sqlite:' . $path));
        $mismatchConnection->shouldReceive('getDatabaseName')->andReturn($path, 'other_database');

        $dbMismatch = Mockery::mock(DatabaseManager::class);
        $dbMismatch->shouldReceive('purge')->andReturnUsing(fn (?string $name = null): mixed => $originalDb->purge($name));
        $dbMismatch->shouldReceive('connection')->with('sqlite')->andReturn($originalDb->connection('sqlite'));
        $dbMismatch->shouldReceive('connection')->with($connectionName)->andReturn($mismatchConnection);
        DB::swap($dbMismatch);

        expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1)
            ->and(Artisan::output())->toContain('separate tenant database');

        $collidingConnection = Mockery::mock(Connection::class);
        $collidingConnection->shouldReceive('getConfig')->andReturn($expectedConfig);
        $collidingConnection->shouldReceive('getPdo')->andReturn(new PDO('sqlite:' . $path));
        $collidingConnection->shouldReceive('getDatabaseName')->andReturn($path);

        $centralColliding = Mockery::mock(Connection::class);
        $centralColliding->shouldReceive('getConfig')->andReturn($expectedConfig);

        $centralCalls = 0;
        $dbColliding = Mockery::mock(DatabaseManager::class);
        $dbColliding->shouldReceive('purge')->andReturnUsing(fn (?string $name = null): mixed => $originalDb->purge($name));
        $dbColliding->shouldReceive('connection')->with('sqlite')->andReturnUsing(function () use (&$centralCalls, $originalDb, $centralColliding): Connection {
            if (! tenancy()->tenant instanceof Tenant) {
                return $originalDb->connection('sqlite');
            }

            $centralCalls++;

            return $centralCalls === 1 ? $originalDb->connection('sqlite') : $centralColliding;
        });
        $dbColliding->shouldReceive('connection')->with($connectionName)->andReturn($collidingConnection);
        DB::swap($dbColliding);

        expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1)
            ->and(Artisan::output())->toContain('separate tenant database');

        $validConnection = Mockery::mock(Connection::class);
        $schema = Mockery::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $validConnection->shouldReceive('getConfig')->andReturn($expectedConfig);
        $validConnection->shouldReceive('getPdo')->andReturn(new PDO('sqlite:' . $path));
        $validConnection->shouldReceive('getDatabaseName')->andReturn($path);
        $validConnection->shouldReceive('getSchemaBuilder')->andReturn($schema);

        $dbValid = Mockery::mock(DatabaseManager::class);
        $dbValid->shouldReceive('purge')->andReturnUsing(fn (?string $name = null): mixed => $originalDb->purge($name));
        $dbValid->shouldReceive('connection')->with('sqlite')->andReturn($originalDb->connection('sqlite'));
        $dbValid->shouldReceive('connection')->with($connectionName)->andReturn($validConnection);
        DB::swap($dbValid);

        $fakeQueueManager = Mockery::mock(QueueManager::class);
        $fakeQueueManager->shouldReceive('connection')->with($queueConnection)->andReturn(Mockery::mock(QueueContract::class));
        app()->instance('queue', $fakeQueueManager);
        app()->instance(QueueManager::class, $fakeQueueManager);

        expect(Artisan::call('tenant:queue-work', ['tenant' => $tenantId, '--once' => true]))->toBe(1)
            ->and(Artisan::output())->toContain('not a database queue');
    } finally {
        DB::swap($originalDb);
        app()->instance('queue', $originalQueue);
        app()->instance(QueueManager::class, $originalQueue);
        DB::purge($connectionName);

        if (is_file($path)) {
            File::delete($path);
        }
    }
});

it('handles inaccessible tenant databases cleanly', function (): void {
    $path = __DIR__ . '/../../../temp/missing-' . Str::random(12) . '.sqlite';
    $tenant = workTestTenant(['database' => ['driver' => 'sqlite', 'database' => $path]]);

    expect(Artisan::call('tenant:queue-work', ['tenant' => (string) $tenant->getKey(), '--once' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('Unable to start or run the tenant queue worker.');
});

it('processes tenant batches and stores failed jobs in the tenant database', function (): void {
    $path = __DIR__ . '/../../../temp/worker-' . Str::random(12) . '.sqlite';
    touch($path);
    $tenant = workTestTenant(['database' => ['driver' => 'sqlite', 'database' => $path, 'prefix' => '']]);
    $tenantId = (string) $tenant->getKey();
    $connectionName = 'mysql_tenant_' . $tenantId;
    $queueName = 'database_tenant_' . $tenantId;

    try {
        $batchId = tenancy()->run($tenant, function () use ($connectionName, $queueName): string {
            createTenantQueueTables($connectionName);

            return Bus::batch([new CoreTenantQueueJob, new CoreTenantQueueJob(true)])
                ->onConnection($queueName)
                ->onQueue('imports')
                ->allowFailures()
                ->dispatch()
                ->id;
        });

        $status = Artisan::call('tenant:queue-work', [
            'tenant' => $tenantId,
            '--stop-when-empty' => true,
            '--queue' => 'imports',
            '--tries' => 1,
            '--backoff' => 0,
            '--force' => true,
        ]);

        expect(Artisan::output())->toContain('CoreTenantQueueJob')
            ->and($status)->toBe(0);

        Config::set('database.connections.' . $connectionName, ['driver' => 'sqlite', 'database' => $path, 'prefix' => '']);

        expect(DB::connection($connectionName)->table('worker_results')->value('tenant_id'))->toBe($tenant->getKey())
            ->and(DB::connection($connectionName)->table('jobs')->count())->toBe(0)
            ->and(DB::connection($connectionName)->table('failed_jobs')->count())->toBe(1)
            ->and(DB::connection($connectionName)->table('job_batches')->where('id', $batchId)->value('failed_jobs'))->toBe(1)
            ->and(tenancy()->tenant)->toBeNull();
    } finally {
        DB::purge($connectionName);
        Config::set('database.connections.' . $connectionName);

        if (is_file($path)) {
            File::delete($path);
        }
    }
});
