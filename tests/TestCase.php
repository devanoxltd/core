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
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn (): string => 'login')->name('login');
    }
}

