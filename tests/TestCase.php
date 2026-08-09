<?php
declare(strict_types=1);
namespace Devanox\Core\Tests;
use TailwindClassMerge\Laravel\TailwindClassMergeServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Workbench\App\Models\User;
use Illuminate\Support\Facades\Blade;
use Devanox\Core\Providers\CoreServiceProvider;
use Devanox\Core\Providers\RouteServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            RouteServiceProvider::class,
            LivewireServiceProvider::class,
            TailwindClassMergeServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app->make(Repository::class)->set('app.key', 'base64:6J9a1X6u8W0y5Z4v8T5w7Z9e4Y2z8A3x9C7d8F9g2E0=');
        $app->make(Repository::class)->set('app.fallback_locale', 'eng');
        $app->make(Repository::class)->set('auth.providers.users.model', User::class);
        $app->make(Repository::class)->set('core.module_path', 'modules-' . getmypid());
        $app->make(Repository::class)->set('cache.default', 'array');
        $app->make(Repository::class)->set('session.driver', 'array');
        $app->make(Repository::class)->set('database.default', 'sqlite');
        $app->make(Repository::class)->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $storagePath = __DIR__ . '/temp/storage-' . getmypid();
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 00755, true);
        }

        $app->useStoragePath($storagePath);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->make(Repository::class)->set('tenancy.enabled', true);
        $app->make(Repository::class)->set('tenancy.database.central_connection', 'sqlite');
        
        Blade::anonymousComponentPath(
            __DIR__ . '/../workbench/resources/views/layouts',
            'layouts'
        );

        Blade::anonymousComponentPath(
            __DIR__ . '/../workbench/resources/views/components/ui',
            'ui'
        );
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/tenancy');
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn (): string => 'login')->name('login');
    }
}

