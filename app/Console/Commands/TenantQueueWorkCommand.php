<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Devanox\Core\Enums\Tenant\Status;
use Devanox\Core\Models\Tenant;
use Devanox\Core\Support\TenantDatabaseContext;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\Console\Input\ArrayInput;
use Throwable;

use function Devanox\Core\Helpers\tenancy;

#[Signature('tenant:queue-work
    {tenant : The tenant ID (one tenant per worker process)}
    {--queue= : Comma-separated queue names}
    {--name=default : The worker name}
    {--once : Process one job and exit}
    {--stop-when-empty : Exit when the queue is empty}
    {--max-jobs=0 : Exit after processing this many jobs}
    {--max-time=0 : Exit after this many seconds}
    {--sleep=3 : Seconds to sleep when the queue is empty}
    {--rest=0 : Seconds to rest between jobs}
    {--timeout=60 : Default job timeout in seconds (jobs may override it)}
    {--tries=3 : Maximum job attempts; 0 means unlimited}
    {--backoff=5 : Seconds before retrying a failed job}
    {--memory=128 : Worker memory limit in megabytes}
    {--force : Run during maintenance mode}')]
#[Description('Start a database queue worker for one tenant')]
final class TenantQueueWorkCommand extends Command
{
    private const string WORKER_SERVICE = 'queue.worker';

    public function handle(): int
    {
        if (config('tenancy.enabled', false) !== true) {
            $this->error(__('core::tenancy.commands.queue.tenancy_required'));

            return self::FAILURE;
        }

        $id = mb_trim($this->argument('tenant'));

        if ($id === '') {
            $this->error(__('core::tenancy.commands.queue.tenant_id_required'));

            return self::FAILURE;
        }

        $tenantClass = config('tenancy.models.tenant', Tenant::class);

        if (! is_string($tenantClass) || ! is_subclass_of($tenantClass, Model::class) || ! is_subclass_of($tenantClass, TenantContract::class)) {
            $this->error(__('core::tenancy.commands.queue.invalid_model'));

            return self::FAILURE;
        }

        $tenant = $tenantClass::query()->where('status', Status::Active->value)->find($id);

        if (! $tenant instanceof TenantContract) {
            $this->error(__('core::tenancy.commands.queue.active_tenant_not_found'));

            return self::FAILURE;
        }

        $tenantConfig = collect($tenant->config)->toArray();
        $database = data_get($tenantConfig, 'database.database');

        if (! is_string($database) || mb_trim($database) === '') {
            $this->error(__('core::tenancy.commands.queue.database_configuration_missing'));

            return self::FAILURE;
        }

        $tenantKey = $tenant->getKey();

        if ((! is_string($tenantKey) && ! is_int($tenantKey)) || (string) $tenantKey !== $id) {
            $this->error(__('core::tenancy.commands.queue.invalid_tenant_id'));

            return self::FAILURE;
        }

        $centralName = config('tenancy.database.central_connection');

        if (! is_string($centralName) || $centralName === '' || ! is_array(config('database.connections.' . $centralName))) {
            $this->error(__('core::tenancy.commands.queue.invalid_configuration'));

            return self::FAILURE;
        }

        $databaseConnection = 'mysql_tenant_' . $tenantKey;
        $queueConnection = 'database_tenant_' . $tenantKey;
        $cacheStore = $queueConnection;

        DB::purge($databaseConnection);

        try {
            return tenancy()->run($tenant, function () use ($database, $databaseConnection, $queueConnection, $cacheStore, $centralName): int {
                $queue = config('queue.connections.' . $queueConnection);
                $databaseConfig = config('database.connections.' . $databaseConnection);
                $centralConfig = config('database.connections.' . $centralName);
                $cache = config('cache.stores.' . $cacheStore);
                $retryAfter = data_get($queue, 'retry_after');
                $timeout = $this->option('timeout');
                $tries = $this->option('tries');
                $backoff = $this->option('backoff');

                if (config('tenancy.current') !== tenancy()->tenant
                    || config('database.default') !== $databaseConnection
                    || config('queue.default') !== $queueConnection
                    || config('cache.default') !== $cacheStore
                    || ! is_array($databaseConfig)
                    || ($databaseConfig['database'] ?? null) !== $database
                    || resolve(TenantDatabaseContext::class)->matchesCentralDatabase($databaseConfig, $centralConfig)
                    || ! is_array($queue)
                    || ($queue['driver'] ?? null) !== 'database'
                    || ($queue['connection'] ?? null) !== $databaseConnection
                    || ! is_string($queue['table'] ?? null) || $queue['table'] === ''
                    || ! is_numeric($retryAfter) || (int) $retryAfter <= 0
                    || ! ctype_digit((string) $timeout)
                    || (int) $timeout <= 0 || (int) $timeout >= (int) $retryAfter
                    || ! ctype_digit((string) $tries) || ! ctype_digit((string) $backoff)
                    || ! is_array($cache) || ($cache['driver'] ?? null) !== 'database'
                    || ($cache['connection'] ?? null) !== $databaseConnection
                    || config('queue.batching.driver', 'database') !== 'database'
                    || ! in_array(config('queue.failed.driver'), ['database', 'database-uuids'], true)) {
                    $this->error(__('core::tenancy.commands.queue.invalid_configuration'));

                    return self::FAILURE;
                }

                config(['queue.batching.database' => $databaseConnection, 'queue.failed.database' => $databaseConnection]);
                DB::purge($databaseConnection);
                $connection = DB::connection($databaseConnection);
                $connection->getPdo();

                if ($connection->getDatabaseName() !== $database) {
                    $this->error(__('core::tenancy.commands.queue.database_not_isolated'));

                    return self::FAILURE;
                }

                $resolvedTenantConfig = $connection->getConfig();
                $resolvedCentralConfig = DB::connection($centralName)->getConfig();

                if (! is_array($resolvedTenantConfig) || ! is_array($resolvedCentralConfig)
                    || resolve(TenantDatabaseContext::class)->matchesCentralDatabase($resolvedTenantConfig, $resolvedCentralConfig)) {
                    $this->error(__('core::tenancy.commands.queue.database_not_isolated'));

                    return self::FAILURE;
                }

                foreach ([$queue['table'], config('queue.batching.table', 'job_batches'), config('queue.failed.table', 'failed_jobs'), $cache['table'] ?? null] as $table) {
                    if (! is_string($table) || $table === '' || ! $connection->getSchemaBuilder()->hasTable($table)) {
                        $this->error(__('core::tenancy.commands.queue.table_missing'));

                        return self::FAILURE;
                    }
                }

                $this->resetQueueServices($cacheStore);

                if (! $this->laravel->make(QueueManager::class)->connection($queueConnection) instanceof DatabaseQueue) {
                    $this->error(__('core::tenancy.commands.queue.database_queue_required'));

                    return self::FAILURE;
                }

                /** @var Worker $worker */
                $worker = $this->laravel->make(self::WORKER_SERVICE);

                $command = new WorkCommand($worker, Cache::store($cacheStore));
                $command->setLaravel($this->laravel);

                $arguments = ['connection' => $queueConnection];

                foreach (['queue', 'name', 'once', 'stop-when-empty', 'max-jobs', 'max-time', 'sleep', 'rest', 'timeout', 'tries', 'backoff', 'memory', 'force'] as $option) {
                    $value = $this->option($option);

                    if ($value !== null && $value !== false) {
                        $arguments['--' . $option] = $value;
                    }
                }

                return $command->run(new ArrayInput($arguments, $command->getDefinition()), $this->output);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->error(__('core::tenancy.commands.queue.worker_failed'));

            return self::FAILURE;
        } finally {
            $this->resetQueueServices($cacheStore);
            DB::purge($databaseConnection);
        }
    }

    private function resetQueueServices(string $cacheStore): void
    {
        foreach (['queue', 'queue.connection', self::WORKER_SERVICE, 'queue.failer', BatchRepository::class, DatabaseBatchRepository::class] as $service) {
            $this->laravel->forgetInstance($service);
        }

        Facade::clearResolvedInstance('queue');
        Cache::forgetDriver($cacheStore);
    }
}
