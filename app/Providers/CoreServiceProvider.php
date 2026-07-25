<?php

declare(strict_types=1);

namespace Devanox\Core\Providers;

use Devanox\Core\Console\Commands\CleanUp;
use Devanox\Core\Console\Commands\LicenseCheck;
use Devanox\Core\Console\Commands\MigrateCheck;
use Devanox\Core\Console\Commands\Module\AllList;
use Devanox\Core\Console\Commands\Module\Disable;
use Devanox\Core\Console\Commands\Module\Enable;
use Devanox\Core\Console\Commands\Module\Migrate;
use Devanox\Core\Core;
use Devanox\Core\Http\Middleware\InstallApp;
use Devanox\Core\Http\Middleware\License;
use Devanox\Core\Support\Module;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RuntimeException;

final class CoreServiceProvider extends ServiceProvider
{
    private string $packageNameLower = 'core';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->checkModulesCapabilities();

        $this->mergeConfigFrom(__DIR__ . '/../../config/core.php', $this->packageNameLower);

        $providers = Module::providers();

        // Register module service providers
        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }

        $this->app->singleton(Core::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerMiddleware();
        $this->loads();
        $this->publishFiles();

        $this->registerCommands();

        $this->booted(function (): void {
            $this->registerCommandSchedules();
        });
    }

    private function checkModulesCapabilities(): void
    {
        $modules = Module::get()->where('enabled', true);

        /** @var array<int, string> $moduleIds */
        $moduleIds = $modules->pluck('id')->filter()->toArray();

        foreach ($modules as $module) {
            $missing = array_diff($module->config->requiredModules ?? [], $moduleIds);

            if ($missing !== []) {
                $missingId = reset($missing);
                Module::disable($module->name);

                $message = sprintf('Module %s requires a module that is not installed. Module %s is disabled. Required module ID: %s', $module->name, $module->name, $missingId);
                Log::notice($message);

                throw new RuntimeException($message);
            }
        }
    }

    private function registerMiddleware(): void
    {
        $middlewares = [
            InstallApp::class,
            License::class,
        ];

        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        foreach ($middlewares as $middleware) {
            $kernel->pushMiddleware($middleware);
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AllList::class,
                Disable::class,
                Enable::class,
                Migrate::class,
                CleanUp::class,
                MigrateCheck::class,
                LicenseCheck::class,
            ]);
        }
    }

    private function registerCommandSchedules(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $schedule = $this->app->make(Schedule::class);
        $event = $schedule->command('devanox:license-check')
            ->dailyAt('08:00')
            ->timezone('UTC')
            ->environments(['production'])
            ->runInBackground();

        $serverUrl = config('core.url.server');

        if (is_string($serverUrl)) {
            $event->pingBefore($serverUrl);
        }
    }

    private function loads(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/core.php', $this->packageNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/core.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->packageNameLower);

        $this->loadTranslationsFrom(__DIR__ . '/../../lang', $this->packageNameLower);
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../lang');

        // TODO: Handle tenant migrations after implementing multi-tenancy
        // if (! tenant()) {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        // }

        $this->registerComponents();
        $this->registerLivewireComponents();
    }

    private function publishFiles(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../config/core.php' => config_path($this->packageNameLower . '.php'),
        ], [$this->packageNameLower, $this->packageNameLower . '-config']);

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/' . $this->packageNameLower),
        ], [$this->packageNameLower, $this->packageNameLower . '-views']);

        $this->publishes([
            __DIR__ . '/../../lang' => $this->app->langPath('vendor/' . $this->packageNameLower),
        ], [$this->packageNameLower, $this->packageNameLower . '-lang']);

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/' . $this->packageNameLower),
        ], [$this->packageNameLower, $this->packageNameLower . '-assets']);

        $this->publishesMigrations([
            __DIR__ . '/../../database/migrations/publishable' => database_path('migrations'),
        ], [$this->packageNameLower, $this->packageNameLower . '-migrations']);
    }

    private function registerComponents(): void
    {
        $componentPath = __DIR__ . '/../View/Components';

        if (is_dir($componentPath)) {
            Blade::componentNamespace('Devanox\\Core\\View\\Components', $this->packageNameLower);
        }

        $anonymousComponentPath = __DIR__ . '/../../resources/views/components';

        if (is_dir($anonymousComponentPath)) {
            Blade::anonymousComponentPath($anonymousComponentPath, $this->packageNameLower);
        }
    }

    private function registerLivewireComponents(): void
    {
        $classDirectory = __DIR__ . '/../Livewire';
        $viewDirectory = __DIR__ . '/../../resources/views/livewire';

        if (is_dir($classDirectory)) {
            $namespace = 'Devanox\\Core\\Livewire';

            Livewire::addLocation(classNamespace: $namespace);

            Livewire::addNamespace(
                namespace: $this->packageNameLower,
                classNamespace: $namespace,
                classPath: $classDirectory,
                classViewPath: $viewDirectory,
            );
        }

        $viewDirectory = __DIR__ . '/../../resources/views/components/livewire';

        if (is_dir($viewDirectory)) {
            Livewire::addLocation(
                viewPath: $viewDirectory,
            );

            Livewire::addNamespace(
                namespace: $this->packageNameLower,
                viewPath: $viewDirectory,
            );
        }
    }
}
