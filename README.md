<div align="center">
    <h1>Core</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://img.shields.io/packagist/v/devanoxltd/core.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://img.shields.io/packagist/php-v/devanoxltd/core.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://badge.laravel.cloud/badge/devanoxltd/core?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/devanoxltd/core/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/devanoxltd/core/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://img.shields.io/packagist/dt/devanoxltd/core.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Core package for Laravel projects providing module support, helpers, and multi-tenancy capabilities. This package is developed for use in Devanox Private Limited projects.

## Requirements

- PHP 8.5 or higher
- Laravel 13.x

## Installation

You can install the package via Composer:

```bash
composer require devanoxltd/core
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="core"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="core-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="core-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="core-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="core-lang"
```

> **Note on translations:** This package ships its translations under the `eng` locale. To use them in a Laravel project whose default locale is `en`, set `APP_FALLBACK_LOCALE=eng` in your application's `.env` file. You can still publish the language files and create an `en` version under `lang/vendor/core/` to override individual strings.

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="core-assets"
```

## Tenancy Setup

If you are using the multi-tenancy features of this package, you need to publish the tenancy configuration and migrations, and then run the installation command:

```bash
php artisan vendor:publish --tag="tenancy-config"
php artisan vendor:publish --tag="tenancy-migrations"
php artisan tenancy:install
php artisan migrate
```

Next, register the tenant routes in your Laravel application's `bootstrap/app.php` file using the `withRouting` method's `then` callback:

```php
use Devanox\Core\Http\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;

// ...

->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        if (file_exists(base_path('routes/tenant.php'))) {
            Route::middleware([
                'web',
                PreventAccessFromCentralDomains::class,
            ])
                ->name('tenant.')
                ->group(base_path('routes/tenant.php'));
        }
    }
)
```

### Extending Tenancy Models

If your host application needs to add custom relationships or logic to the `Tenant` or `Domain` models, you can create your own models extending the package's base models.

First, create your custom model extending the base model:

```php
namespace App\Models;

use Devanox\Core\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    // Add custom relationships or logic here
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
```

Then, update the `config/tenancy.php` configuration file to use your new model:

```php
    'models' => [
        'tenant' => App\Models\Tenant::class,
        'domain' => Devanox\Core\Models\Domain::class,
    ],
```

### Tenant database queues

When `tenancy.enabled` is `true`, Core automatically uses the active tenant database for database-backed batch and failed-job repositories. Tenant initialization fails rather than falling back to the central database when tenant database configuration is missing or matches the central connection. With tenancy disabled, Laravel's normal central repositories are used and tenant contexts cannot be started. Restart long-running processes after changing configuration. This does not start workers, and other queue or batch providers retain their existing behavior.

Run a separate central worker and one supervised `php artisan tenant:queue-manage --queue=default` manager process. It discovers **active** tenants on each pass and starts up to four isolated, short-lived tenant workers concurrently; use `--workers` and `--interval` to tune capacity and discovery frequency. Each worker processes at most one job per pass, so a busy tenant cannot monopolize the pool. For an explicit comma-separated `--queue` list, the manager rotates the priority on each pass to prevent a busy first queue from starving the others. New and newly activated tenants are discovered automatically after their database and required tables are ready; failures are reported and retried on later passes. A worker rechecks that its tenant is active before initialization. Suspending a tenant does not interrupt an already running job; it prevents later worker starts. Use `--once` for one pass, and monitor the manager's streamed output and tenant failed-job tables: Laravel may report a failed job while the worker exits successfully.

For Supervisor, configure a single program for the manager with `autorestart=true`, `stopasgroup=false`, `killasgroup=false`, and `stopwaitsecs` longer than the longest allowed job-specific timeout plus startup and cleanup time. Set the command's working directory to the application's root. The manager requires PHP's `pcntl` extension, stops launching children on SIGTERM/SIGINT, and waits for in-flight workers. It does not impose a separate child-process timeout, because jobs may override `--timeout`. Monitor stalled workers and set bounded job timeouts: an unbounded or stuck child can delay shutdown indefinitely. Avoid force-killing workers except as an operational last resort. Run the central queue worker separately. Starting additional managers increases concurrency; jobs must remain idempotent because retries and process crashes can cause duplicate execution.

Alternatively, run a supervised `php artisan tenant:queue-work TENANT_ID --queue=default` process for a specific active tenant. Include any other queue names used by the application. A long-lived worker checks status at startup, so restart it when a tenant is suspended. Apply the tenant queue, batch, failed-job, and database-cache migrations first, and set `retry_after` longer than **every** worker and job-specific timeout, with a safety margin. Laravel allows individual jobs to override the worker timeout; the worker can validate only the configured default, not every job. The manager uses a one-job daemon rather than Laravel's `--once` path so Laravel installs its timeout handler. The worker validates the tenant database before reading jobs and does not migrate jobs from an existing central queue.

The pool launches a fresh PHP process per active tenant per pass, including empty queues. Capacity and pickup latency depend on PHP startup, database latency, job duration, tenant count, `--workers`, and `--interval`; a slow job holds a worker slot until it finishes. Measure queue age, backlog, worker failures, CPU, memory, process-start rate and pass duration under representative load. Increase workers only within database and host capacity, and add managers cautiously because they can process the same tenant concurrently.

`Devanox\Core\Support\TenantDatabaseContext` provides `run($tenantId, $callback)`, `connection($tenantId)`, and `activeTenantId()` for operations that need a validated database context. Use the configured tenant model and keep application-specific notification and webhook logic in the host application. Provision separate databases and restrict their credentials: configuration comparisons cannot detect every hostname, socket, or filesystem alias.

## Usage

The `devanoxltd/core` package provides a rich set of tools to manage modules and multi-tenancy in your Laravel application.

### Artisan Commands

#### Module Management
- `php artisan module:list`: List all installed modules and their status.
- `php artisan module:enable {module?}`: Enable a specific module. Use the `--all` option to enable all modules.
- `php artisan module:disable {module?}`: Disable a specific module. Use the `--all` option to disable all modules.
- `php artisan module:migrate {module?}`: Run migrations for a specific module or all modules.

#### Core Utilities
- `php artisan app:clean-up`: Clean up system data, caches, or logs.
- `php artisan migrate:check`: Check for any pending database migrations.
- `php artisan devanox:license-check`: Check licenses for the package.

#### Tenancy Management (when enabled)
- `php artisan tenancy:install`: Generate tenancy base models and prepare configuration.
- `php artisan tenant:create-database {id}`: Create a database for a specific tenant.
- `php artisan tenant {artisanCommand}`: Run a specific Artisan command for a specific tenant or all tenants.

### Running Code in a Tenant's Context

You can safely execute code under a specific tenant's context using the `tenancy()->run()` method. This will temporarily set the tenant, switch the database connections and other configured services, run your callback, and then completely restore the original configuration and tenant state.

```php
use App\Models\Tenant;

$tenant = Tenant::find(1);

tenancy()->run($tenant, function ($tenant) {
    // This code will run within the context of the tenant
    // e.g., using the tenant's database connection
});
```

### Helper Functions

The package registers several global helper functions for convenience:

- `isAppInstalled(): bool` - Checks if the application has been installed (verifies the existence of the `storage/installed` file).
- `modules(?bool $status = true): Collection` - Retrieve a collection of modules. Pass `true` for enabled, `false` for disabled, and `null` for all modules.
- `tenancy(): \Devanox\Core\Support\Tenancy` - Resolves the main `Tenancy` manager instance.
- `tenant(): ?\Devanox\Core\Contracts\Models\Tenant` - Returns the currently identified `Tenant` model, or `null` if tenancy is disabled or no tenant is resolved.

### Middleware

The service provider automatically registers the following middleware to the HTTP kernel:
- `Devanox\Core\Http\Middleware\InstallApp`: Ensures the application is installed before handling requests.

If tenancy is enabled, it also prepends `PreventAccessFromCentralDomains` to protect tenant-specific routes from being accessed via central application domains.

### Blade & Livewire Components

The package registers its Blade components under the `core::` namespace and its Livewire components natively.
You can use them in your views using the `core::` prefix for blade views, or standard Livewire syntax for any bundled Livewire components.

### Facades

The package provides the `\Devanox\Core\Facades\Core` facade, which is automatically aliased as `Core` via `composer.json` for easy access to core package functionality.

## Testing

Run the package test suites with Composer:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Core! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Mr Chetan](https://github.com/devanoxltd)
- [All Contributors](../../contributors)

## License

Core is proprietary software licensed under the [Proprietary License](LICENSE.md).
