<?php

declare(strict_types=1);

namespace Devanox\Core\Providers;

use Composer\InstalledVersions;
use Devanox\Core\Console\Commands\CleanUp;
use Devanox\Core\Console\Commands\LicenseCheck;
use Devanox\Core\Console\Commands\MigrateCheck;
use Devanox\Core\Console\Commands\Module\AllList;
use Devanox\Core\Console\Commands\Module\Disable;
use Devanox\Core\Console\Commands\Module\Enable;
use Devanox\Core\Console\Commands\Module\Migrate;
use Devanox\Core\Console\Commands\TenantCommand;
use Devanox\Core\Console\Commands\TenantCreateDatabaseCommand;
use Devanox\Core\Console\Commands\TenantInstallCommand;
use Devanox\Core\Core;
use Devanox\Core\Events\Tenant\Created as TenantCreatedEvent;
use Devanox\Core\Events\Tenant\DatabaseCreated as TenantDatabaseCreatedEvent;
use Devanox\Core\Http\Middleware\InstallApp;
use Devanox\Core\Http\Middleware\License;
use Devanox\Core\Http\Middleware\PreventAccessFromCentralDomains;
use Devanox\Core\Listeners\Tenant\Created as TenantCreatedListener;
use Devanox\Core\Listeners\Tenant\DatabaseCreated as TenantDatabaseCreatedListener;
use Devanox\Core\Support\Module;
use Devanox\Core\Support\Tenancy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RuntimeException;

use function Devanox\Core\Helpers\tenant;

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
        $this->mergeConfigFrom(__DIR__ . '/../../config/tenancy.php', 'tenancy');

        AboutCommand::add('Core', function (): array {
            /** @var array<int, string> $domains */
            $domains = config('tenancy.central_domains', []);

            return [
                'Version' => InstalledVersions::getPrettyVersion('devanoxltd/core'),
                'Tenant' => $this->isTenancyEnabled() ? 'Enabled' : 'Disabled',
                'Central Domains' => $this->isTenancyEnabled() ? implode(', ', $domains) ?: 'None' : 'N/A',
            ];
        });

        $this->app->singleton(Tenancy::class, fn (Application $app): Tenancy => new Tenancy);

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
        $this->setupTenancy();
        $this->registerMiddleware();
        $this->loads();
        $this->publishFiles();

        $this->registerCommands();

        $this->booted(function (): void {
            $this->registerCommandSchedules();
        });

        $this->registerEventListeners();
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

        $this->callAfterResolving(Kernel::class, function (Kernel $kernel) use ($middlewares): void {
            /** @var HttpKernel $httpKernel */
            $httpKernel = $kernel;

            foreach ($middlewares as $middleware) {
                $httpKernel->pushMiddleware($middleware);
            }
        });
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

            if ($this->isTenancyEnabled()) {
                $this->commands([
                    TenantCommand::class,
                    TenantCreateDatabaseCommand::class,
                    TenantInstallCommand::class,
                ]);
            }
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
        $this->loadRoutesFrom(__DIR__ . '/../../routes/core.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->packageNameLower);

        $this->loadTranslationsFrom(__DIR__ . '/../../lang', $this->packageNameLower);
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../lang');

        if (! tenant()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

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

        // If any assets are present in the package's public directory, publish them to the application's public directory
        // $this->publishes([
        //     __DIR__ . '/../../public' => public_path('vendor/' . $this->packageNameLower),
        // ], [$this->packageNameLower, $this->packageNameLower . '-assets']);

        $this->publishesMigrations(
            $this->getMigrationsToPublish(__DIR__ . '/../../database/migrations/publishable'),
            [$this->packageNameLower, $this->packageNameLower . '-migrations'],
        );

        // Publish the tenancy configuration and migrations
        $this->publishes([
            __DIR__ . '/../../config/tenancy.php' => config_path('tenancy.php'),
        ], [$this->packageNameLower, 'tenancy-config']);

        $this->publishesMigrations(
            $this->getMigrationsToPublish(__DIR__ . '/../../database/migrations/tenancy'),
            [$this->packageNameLower, 'tenancy-migrations'],
        );
    }

    /**
     * Get the migrations that have not yet been published.
     *
     * @return array<string, string>
     */
    private function getMigrationsToPublish(string $directory): array
    {
        $paths = [];

        $files = glob($directory . '/*.php');

        foreach ($files ?: [] as $file) {
            $filename = basename($file);

            // Strip the timestamp to get the base migration name
            $migrationName = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);

            // Check if any file in the migrations directory ends with this name
            $existing = glob(database_path('migrations/*_' . $migrationName));

            if ($existing === [] || $existing === false) {
                $paths[$file] = database_path('migrations/' . $filename);
            }
        }

        return $paths;
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

    private function registerEventListeners(): void
    {
        if (! $this->isTenancyEnabled()) {
            return;
        }

        Event::listen(
            TenantCreatedEvent::class,
            TenantCreatedListener::class,
        );

        Event::listen(
            TenantDatabaseCreatedEvent::class,
            TenantDatabaseCreatedListener::class,
        );
    }

    private function isTenancyEnabled(): bool
    {
        return config('tenancy.enabled', false) === true;
    }

    private function setupTenancy(): void
    {
        if (! $this->isTenancyEnabled()) {
            return;
        }

        $this->app->make(Tenancy::class)->initializeTenant();
        $tenancyMiddleware = [
            // Even higher priority than the initialization middleware
            PreventAccessFromCentralDomains::class,
        ];

        $this->callAfterResolving(Kernel::class, function (Kernel $kernel) use ($tenancyMiddleware): void {
            /** @var HttpKernel $httpKernel */
            $httpKernel = $kernel;

            foreach (array_reverse($tenancyMiddleware) as $middleware) {
                $httpKernel->prependToMiddlewarePriority($middleware);
            }
        });
    }
}
