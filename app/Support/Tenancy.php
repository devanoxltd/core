<?php

declare(strict_types=1);

namespace Devanox\Core\Support;

use Devanox\Core\Contracts\Models\Domain as DomainContract;
use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Devanox\Core\Enums\Domain\Status as DomainStatus;
use Devanox\Core\Enums\Tenant\Status;
use Devanox\Core\Helpers\Configuration;
use Devanox\Core\Models\Tenant;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class Tenancy
{
    public ?TenantContract $tenant;

    public function __construct()
    {
        $this->tenant = $this->getTenantByDomain($this->requestDomain());
    }

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->tenant ? ($this->tenant->{$key} ?? $default) : $default;
    }

    public function requestDomain(): string
    {
        $domain = '';

        try {
            $domain = request()->getHost();
        } catch (Exception) {
            $appDomain = config('app.domain', 'localhost');
            $domain = is_string($appDomain) ? $appDomain : 'localhost';
        }

        return $domain;
    }

    /**
     * @return array<int, string>
     */
    public function centralDomains(): array
    {
        /** @var array<int, string> $domains */
        $domains = config('tenancy.central_domains', []);

        return $domains;
    }

    public function appDomain(?string $hostname = null): string
    {
        if (! $hostname) {
            $hostname = $this->requestDomain();
        }

        $centralDomains = $this->centralDomains();

        $matched = collect($centralDomains)
            ->first(fn (string $domain): bool => Str::endsWith($hostname, $domain));

        $appDomain = config('app.domain', 'localhost');

        return $matched ?: (is_string($appDomain) ? $appDomain : 'localhost');
    }

    public function isAppSubdomain(?string $hostname = null): bool
    {
        if (! $hostname) {
            $hostname = $this->requestDomain();
        }

        if ($hostname === $this->appDomain()) {
            return false;
        }

        return Str::endsWith($hostname, $this->centralDomains());
    }

    public function isAppDomain(?string $hostname = null): bool
    {
        if (! $hostname) {
            $hostname = $this->requestDomain();
        }

        return in_array($hostname, $this->centralDomains(), true);
    }

    public function tenantDomain(string $hostname): string
    {
        return $this->isAppSubdomain($hostname) ? Str::before($hostname, '.' . $this->appDomain()) : $hostname;
    }

    public function getTenantByDomain(string $domain): ?TenantContract
    {
        if ($this->isAppDomain($domain)) {
            return null;
        }

        $domain = $this->tenantDomain($domain);
        $tenant = null;

        /** @var class-string<TenantContract&Model> $tenantClass */
        $tenantClass = config('tenancy.models.tenant', Tenant::class);

        /** @var Builder<TenantContract&Model> $query */
        $query = $tenantClass::query();

        /** @var TenantContract|null $tenant */
        try {
            $tenant = $query
                ->whereHas('domains', function (Builder $query) use ($domain): void {
                    $query->where('domain', $domain)
                        ->where('status', DomainStatus::Active);
                })
                ->with([
                    'domains' => function (Relation $query) use ($domain): void {
                        $query->where('domain', $domain)
                            ->where('status', DomainStatus::Active);
                    },
                ])
                ->where('status', Status::Active);

            /** @var TenantContract|null $tenant */
            $tenant = $query->first();
        } catch (QueryException) {
            $tenant = null;
        } catch (Exception $e) {
            Log::error($e->getMessage(), ['domain' => $domain]);
            $tenant = null;
        }

        return $tenant;
    }

    public function setTenant(TenantContract $tenant): ?string
    {
        $this->tenant = $tenant;

        return $this->setTenantConfig();
    }

    public function setTenantConfig(): ?string
    {
        if (! $this->tenant) {
            return null;
        }

        /** @var Collection<int, DomainContract&Model> $domains */
        $domains = $this->tenant->domains;

        $config = [
            'app' => [],
            'database' => ['connections' => []],
            'cache' => ['stores' => []],
            'queue' => ['connections' => []],
            'tenancy' => [
                'current' => $this->tenant,
                'database' => [],
            ],
        ];

        $domain = $domains->where('status', DomainStatus::Active)->first();

        if ($domain !== null) {
            $config['app']['url'] = $domain->url;
        } else {
            $config['app']['url'] = $this->requestDomain();
        }

        /** @var array<string, mixed> $tenantConfigs */
        $tenantConfigs = collect($this->tenant->config)->toArray();

        /** @var array<string, mixed> $tenantDbConfigs */
        $tenantDbConfigs = $tenantConfigs['database'] ?? [];

        if (empty($tenantDbConfigs)) {
            return null;
        }

        // unset database from tenantConfigs to avoid overwriting the database connection settings
        unset($tenantConfigs['database']);

        $tenantKey = $this->tenant->getKey();
        $tenantId = is_scalar($tenantKey) ? (string) $tenantKey : '';
        $tenantConnectionName = 'mysql_tenant_' . $tenantId;
        $tenantDefaultConnectionName = 'database_tenant_' . $tenantId;

        /** @var array<string, mixed> $mysqlConnection */
        $mysqlConnection = config('database.connections.mysql', []);

        /** @var array<string, mixed> $cacheDatabase */
        $cacheDatabase = config('cache.stores.database', []);

        /** @var array<string, mixed> $queueDatabase */
        $queueDatabase = config('queue.connections.database', []);

        $config['database']['connections'][$tenantConnectionName] = array_merge($mysqlConnection, $tenantDbConfigs);

        $config['cache']['stores'][$tenantDefaultConnectionName] = $cacheDatabase;
        $config['cache']['stores'][$tenantDefaultConnectionName]['connection'] = $tenantConnectionName;

        $config['queue']['connections'][$tenantDefaultConnectionName] = $queueDatabase;
        $config['queue']['connections'][$tenantDefaultConnectionName]['connection'] = $tenantConnectionName;

        $config['database']['default'] = $tenantConnectionName;
        $config['tenancy']['database']['tenant_connection'] = $tenantConnectionName;
        $config['cache']['default'] = $tenantDefaultConnectionName;
        $config['queue']['default'] = $tenantDefaultConnectionName;

        // merge tenant configs into the main config array if any tenant configs are present
        if (! empty($tenantConfigs)) {
            $config = array_merge($config, $tenantConfigs);
        }

        Configuration::apply($config);

        return $tenantConnectionName;
    }

    public function initializeTenant(): void
    {
        if ($this->tenant) {
            $tenantConnectionName = $this->setTenantConfig();

            if (! $tenantConnectionName) {
                throw new Exception(__('core::tenancy.exceptions.database_configurations_not_found', ['name' => (string) $this->tenant->name]));
            }

            if (! DB::connection($tenantConnectionName)->getDatabaseName()) {
                throw new Exception(__('core::tenancy.exceptions.database_configurations_not_found', ['name' => (string) $this->tenant->name]));
            }
        }
    }

    public function unsetTenant(): void
    {
        $this->tenant = null;

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        /** @var array<string, mixed> $config */
        $config = [
            'database' => [
                'default' => is_string($centralConnection) ? $centralConnection : 'mysql',
            ],
            'cache' => [
                'default' => 'database',
            ],
            'queue' => [
                'default' => 'database',
            ],
            'tenancy' => [
                'current' => null,
            ],
        ];

        Configuration::apply($config);
    }

    /**
     * Run a callback under a specific tenant context.
     *
     * @template T
     *
     * @param  callable(TenantContract): T  $callback
     * @return T
     */
    public function run(TenantContract $tenant, callable $callback): mixed
    {
        $originalTenant = $this->tenant;

        /** @var array<string, mixed> $originalConfig */
        $originalConfig = config()->all();

        try {
            $this->setTenant($tenant);

            return $callback($tenant);
        } finally {
            $this->tenant = $originalTenant;
            config($originalConfig);
        }
    }
}
