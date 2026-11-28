<?php

declare(strict_types=1);

namespace Devanox\Core\Support;

use Closure;
use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Devanox\Core\Models\Tenant;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

use function Devanox\Core\Helpers\tenancy;

final class TenantDatabaseContext
{
    public function run(?string $tenantId, Closure $callback): mixed
    {
        if ($tenantId !== null) {
            if (config('tenancy.enabled', false) !== true || $tenantId === '') {
                throw new RuntimeException(__('core::tenancy.exceptions.context_unavailable'));
            }

            $tenantClass = config('tenancy.models.tenant', Tenant::class);

            if (! is_string($tenantClass) || ! is_subclass_of($tenantClass, Model::class) || ! is_subclass_of($tenantClass, TenantContract::class)) {
                throw new RuntimeException(__('core::tenancy.exceptions.invalid_model_configuration'));
            }

            $tenant = $tenantClass::query()->findOrFail($tenantId);

            return tenancy()->run($tenant, function () use ($callback, $tenantId): mixed {
                $this->connection($tenantId);

                return $callback();
            });
        }

        $tenancy = tenancy();
        $originalTenant = $tenancy->tenant;
        $originalConfig = config()->all();

        try {
            if ($originalTenant instanceof TenantContract || config('tenancy.current') !== null) {
                $tenancy->unsetTenant();
            }

            return $callback();
        } finally {
            $tenancy->tenant = $originalTenant;
            config($originalConfig);
        }
    }

    public function connection(?string $tenantId, ?Tenancy $tenancy = null): Connection
    {
        $tenancy ??= tenancy();

        if ($tenantId !== $this->activeTenantId($tenancy)) {
            throw new RuntimeException(__('core::tenancy.exceptions.context_mismatch'));
        }

        if ($tenantId === null) {
            return $this->centralConnection($tenancy);
        }

        $connectionName = 'mysql_tenant_' . $tenantId;
        $tenant = $tenancy->tenant;
        $database = data_get($tenant?->config, 'database.database');
        $tenantConfig = config('database.connections.' . $connectionName);
        $centralConfig = $this->centralConnection($tenancy)->getConfig();

        if ($tenantId === '' || config('tenancy.enabled') !== true || config('database.default') !== $connectionName
            || config('tenancy.current') !== $tenant || ! is_string($database) || mb_trim($database) === ''
            || ! is_array($tenantConfig) || ! is_array($centralConfig) || ($tenantConfig['database'] ?? null) !== $database
            || $this->matchesCentralDatabase($tenantConfig, $centralConfig)) {
            throw new RuntimeException(__('core::tenancy.exceptions.context_initialization_failed'));
        }

        $connection = DB::connection($connectionName);
        $actualConfig = $connection->getConfig();

        if (! is_array($actualConfig)) {
            throw new RuntimeException(__('core::tenancy.exceptions.context_initialization_failed'));
        }

        foreach (['driver', 'database', 'host', 'port', 'unix_socket', 'username'] as $key) {
            $configuredTenantValue = data_get($tenant?->config, 'database.' . $key);

            if (($actualConfig[$key] ?? null) !== ($tenantConfig[$key] ?? null)
                || ($configuredTenantValue !== null && $configuredTenantValue !== ($tenantConfig[$key] ?? null))) {
                throw new RuntimeException(__('core::tenancy.exceptions.context_initialization_failed'));
            }
        }

        if ($connection->getDatabaseName() !== $database || $this->matchesCentralDatabase($actualConfig, $centralConfig)) {
            throw new RuntimeException(__('core::tenancy.exceptions.context_initialization_failed'));
        }

        return $connection;
    }

    public function activeTenantId(?Tenancy $tenancy = null): ?string
    {
        $tenant = ($tenancy ?? tenancy())->tenant;

        if (! $tenant instanceof TenantContract) {
            return null;
        }

        $key = $tenant->getKey();

        if (! is_string($key) && ! is_int($key)) {
            throw new RuntimeException(__('core::tenancy.exceptions.invalid_identifier'));
        }

        return (string) $key;
    }

    /**
     * @param  array<array-key, mixed>  $tenant
     * @param  array<array-key, mixed>  $central
     */
    public function matchesCentralDatabase(array $tenant, array $central): bool
    {
        if (($tenant['database'] ?? null) === ':memory:') {
            return false;
        }

        $keys = ($tenant['driver'] ?? null) === 'sqlite'
            ? ['driver', 'database']
            : ['driver', 'host', 'port', 'unix_socket', 'database'];

        return array_all($keys, function (string $key) use ($tenant, $central): bool {
            $tenantValue = $tenant[$key] ?? null;
            $centralValue = $central[$key] ?? null;

            if (in_array($key, ['port', 'unix_socket'], true)) {
                $normalizedTenant = is_scalar($tenantValue) ? (string) $tenantValue : '';
                $normalizedCentral = is_scalar($centralValue) ? (string) $centralValue : '';

                return $normalizedTenant === $normalizedCentral;
            }

            return $tenantValue === $centralValue;
        });
    }

    private function centralConnection(?Tenancy $tenancy = null): Connection
    {
        $connection = config('tenancy.database.central_connection');

        if (is_string($connection) && $connection !== '') {
            return DB::connection($connection);
        }

        if (($tenancy ?? tenancy())->tenant instanceof TenantContract) {
            throw new RuntimeException(__('core::tenancy.exceptions.context_initialization_failed'));
        }

        $default = config('database.default');

        if (! is_string($default) || $default === '') {
            throw new RuntimeException(__('core::tenancy.exceptions.context_initialization_failed'));
        }

        return DB::connection($default);
    }
}
