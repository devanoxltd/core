<?php

declare(strict_types=1);

namespace Devanox\Core\Providers;

use Devanox\Core\Support\TenantDatabaseContext;
use Illuminate\Bus\BatchFactory;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class TenantQueueServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (config('tenancy.enabled', false) !== true) {
            return;
        }

        if (config('queue.batching.driver', 'database') === 'database') {
            $repository = $this->app->make(BatchRepository::class);

            if ($repository::class === DatabaseBatchRepository::class || is_subclass_of($repository, DatabaseBatchRepository::class)) {
                $this->app->bind(function (Application $app): BatchRepository {
                    $context = $app->make(TenantDatabaseContext::class);
                    $tenantId = $context->activeTenantId();
                    $batchDatabase = config('queue.batching.database');

                    throw_if($batchDatabase !== null && ! is_string($batchDatabase), RuntimeException::class, 'Invalid batch database connection.');

                    $connection = $tenantId === null
                        ? $app->make(DatabaseManager::class)->connection($batchDatabase)
                        : $context->connection($tenantId);

                    return new DatabaseBatchRepository(
                        $app->make(BatchFactory::class),
                        $connection,
                        config()->string('queue.batching.table', 'job_batches'),
                    );
                });
            }
        }

        if (in_array(config('queue.failed.driver'), ['database', 'database-uuids'], true)) {
            $this->app->make('queue.failer');
            $this->app->bind('queue.failer', function (Application $app): DatabaseFailedJobProvider|DatabaseUuidFailedJobProvider {
                $context = $app->make(TenantDatabaseContext::class);
                $tenantId = $context->activeTenantId();
                $database = $tenantId === null
                    ? config('queue.failed.database', $app->make(DatabaseManager::class)->getDefaultConnection())
                    : $context->connection($tenantId)->getName();
                $table = config()->string('queue.failed.table', 'failed_jobs');

                if ($database === null && $tenantId === null) {
                    $database = $app->make(DatabaseManager::class)->getDefaultConnection();
                }

                throw_if(! is_string($database) || $database === '', RuntimeException::class, 'Failed job database connection is required.');

                return config('queue.failed.driver') === 'database-uuids'
                    ? new DatabaseUuidFailedJobProvider($app->make(DatabaseManager::class), $database, $table)
                    : new DatabaseFailedJobProvider($app->make(DatabaseManager::class), $database, $table);
            });
        }
    }
}
