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
