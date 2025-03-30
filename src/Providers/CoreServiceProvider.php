<?php

namespace Devanox\Core\Providers;

use Devanox\Core\Http\Middleware\InstallApp;
use Devanox\Core\Http\Middleware\Licence;
use Devanox\Core\Support\Module;
use Exception;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class CoreServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Core';

    protected string $moduleNameLower = 'core';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register your package's services here.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->checkModulesCapabilities();
        $this->registerMiddleware();
        $this->registerCommands();
        $this->loads();
        $this->publishFiles();

        $this->booted(function () {
            $this->registerCommandSchedules();
        });
    }

    private function publishFiles()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => base_path('config/' . $this->moduleNameLower . '.php'),
        ], 'devanox-core');
    }

    private function registerCommandSchedules(): void
    {
        // declare this method in the service provider
        // following is an example of how to register a schedule
        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
        $schedule->command('devanox:licence-check')
        ->dailyAt('08:00')
        ->timezone('UTC')
        ->pingBefore('https://devanox-activate.test') // TODO : update this URL to your production URL
        ->environments(['production'])
        ->runInBackground();
    }

    private function checkModulesCapabilities(): void
    {
        $modules = Module::getModules();
        $moduleIds = [];

        foreach ($modules as $module) {
            $prefix = Module::prefix($module);
            $id = config("{$prefix}.id", null);

            if ($id !== null) {
                $moduleIds[$id] = $module;
            }
        }

        // Check requirements
        foreach ($modules as $module) {
            $prefix = Module::prefix($module);
            $requiredModules = config("{$prefix}.requiredModules", []);

            if (empty($requiredModules)) {
                continue;
            }

            foreach ($requiredModules as $requiredModule) {
                if (! in_array($requiredModule, array_keys($moduleIds))) {
                    Module::disable($module);
                    $message = "Module {$module} requires a module that is not installed. Module {$module} is disabled. Required module ID: {$requiredModule}";
                    Log::notice($message);
                    throw new Exception($message);
                }
            }
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Devanox\Core\Commands\Module\AllList::class,
                \Devanox\Core\Commands\Module\Disable::class,
                \Devanox\Core\Commands\Module\Enable::class,
                \Devanox\Core\Commands\CleanUp::class,
                \Devanox\Core\Commands\LicenceCheck::class,
            ]);
        }
    }

    private function registerMiddleware(): void
    {
        $middlewares = [
            InstallApp::class,
            Licence::class,
        ];

        foreach ($middlewares as $middleware) {
            /** @var \Illuminate\Foundation\Http\Kernel $kernel */
            $kernel = $this->app[Kernel::class];
            $kernel->pushMiddleware($middleware);
        }
    }

    private function loads(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', $this->moduleNameLower);
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', $this->moduleNameLower);
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', $this->moduleNameLower);
        $this->loadJsonTranslationsFrom(__DIR__ . '/../Lang');
        $this->registerComponents();
        $this->registerLivewireComponents();
    }

    private function registerComponents(): void
    {
        $componentPath = __DIR__ . '/../View/Components';

        if (file_exists($componentPath)) {
            Blade::componentNamespace('Devanox\\Core\\View\\Components', $this->moduleNameLower);
        }

        $anonymousComponentPath = __DIR__ . '/../Resources/components';
        if (file_exists($anonymousComponentPath)) {
            Blade::anonymousComponentPath($anonymousComponentPath, $this->moduleNameLower);
        }
    }

    private function registerLivewireComponents(): void
    {
        $directory = __DIR__ . '/../Livewire';

        if (file_exists($directory)) {
            $namespace = 'Devanox\\Core\\Livewire';
            $aliasPrefix = $this->moduleNameLower . '::';

            $this->registerComponentDirectory($directory, $namespace, $aliasPrefix);
        }
    }

    /**
     * Register component directory.
     */
    protected function registerComponentDirectory(string $directory, string $namespace, string $aliasPrefix = ''): void
    {
        $filesystem = new Filesystem;

        /**
         * Directory doesn't existS.
         */
        if (! $filesystem->isDirectory($directory)) {
            return;
        }

        collect($filesystem->allFiles($directory))
            ->map(
                fn(SplFileInfo $file) => str($namespace)
                    ->append("\\{$file->getRelativePathname()}")
                    ->replace(['/', '.php'], ['\\', ''])
                    ->toString()
            )
            ->filter(fn($class) => (is_subclass_of($class, Component::class) && ! (new ReflectionClass($class))->isAbstract()))
            ->each(fn($class) => $this->registerSingleComponent($class, $namespace, $aliasPrefix));
    }

    /**
     * Register livewire single component.
     */
    private function registerSingleComponent(string $class, string $namespace, string $aliasPrefix): void
    {
        $alias = $aliasPrefix . str($class)
            ->after($namespace . '\\')
            ->replace(['/', '\\'], '.')
            ->explode('.')
            // ->map([Str::class, 'kebab'])
            ->map(fn($value) => str($value)->kebab())
            ->implode('.');

        str($class)->endsWith(['\Index', '\index'])
            ? Livewire::component(str($alias)->beforeLast('.index'), $class)
            : Livewire::component($alias, $class);
    }
}
