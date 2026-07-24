<?php

declare(strict_types=1);

namespace Devanox\Core\Traits\Modules;

use Devanox\Core\Support\Module;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use ReflectionClass;

/**
 * This trait is used to register all the necessary components of a module.
 * It is used in the service provider of the module.
 *
 * @phpstan-ignore trait.unused
 */
trait Provider
{
    /**
     * Get the name of the module from the calling class.
     */
    public static function name(): string
    {
        return (string) str(static::class)->replace('Modules\\', '')->before('\\');
    }

    /**
     * Get the kebab-cased name of the module.
     */
    public static function nameLower(): string
    {
        return Module::prefix(self::name());
    }

    /**
     * Get the current directory path of the calling class.
     */
    public static function currentPath(): string
    {
        $reflection = new ReflectionClass(static::class);

        return dirname($reflection->getFileName());
    }

    /**
     * Register the database migrations for the module.
     */
    protected function registerDatabase(): void
    {
        $migrationPath = Module::pathFor(self::name(), 'migrations');

        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Register and merge the configuration files for the module.
     */
    protected function registerConfig(): void
    {
        $configPath = Module::pathFor(self::name(), 'config') . DIRECTORY_SEPARATOR . 'config.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, self::nameLower());
        }

        config([
            self::nameLower() . '.name' => self::name(),
            self::nameLower() . '.name_lower' => self::nameLower(),
            self::nameLower() . '.path' => Module::path(self::name()),
        ]);
    }

    /**
     * Register the views for the module.
     */
    protected function registerViews(): void
    {
        $viewPath = Module::pathFor(self::name(), 'views');

        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, self::nameLower());
        }
    }

    /**
     * Register the translations for the module.
     */
    protected function registerTranslations(): void
    {
        $translationPath = Module::pathFor(self::name(), 'lang');

        if (is_dir($translationPath)) {
            $this->loadTranslationsFrom($translationPath, self::nameLower());
            $this->loadJsonTranslationsFrom($translationPath);
        }
    }

    /**
     * Register the Blade components for the module.
     */
    protected function registerComponents(): void
    {
        $componentPath = Module::pathFor(self::name(), 'components');

        if (is_dir($componentPath)) {
            Blade::componentNamespace('Modules\\' . self::name() . '\\App\\View\\Components', self::nameLower());
        }

        $anonymousComponentPath = Module::pathFor(self::name(), 'components-view');

        if (is_dir($anonymousComponentPath)) {
            Blade::anonymousComponentPath($anonymousComponentPath, self::nameLower());
        }
    }

    /**
     * Register console commands for the module.
     */
    protected function registerCommands(): void
    {
        if (property_exists($this, 'commands') && is_array($this->commands)) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register command schedules for the module.
     * To be implemented by the service provider.
     */
    protected function registerCommandSchedules(): void
    {
        // declare this method in the service provider
        // following is an example of how to register a schedule
        // $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
        // $schedule->command('inspire')->everyMinute();
    }

    /**
     * Register Livewire components and their views for the module.
     */
    protected function registerLivewireComponents(): void
    {
        $classDirectory = Module::pathFor(self::name(), 'livewire');
        $viewDirectory = Module::pathFor(self::name(), 'views') . DIRECTORY_SEPARATOR . 'livewire';

        if (is_dir($classDirectory)) {
            $namespace = 'Modules\\' . self::name() . '\\App\\Livewire';

            Livewire::addLocation(
                classNamespace: $namespace,
            );

            Livewire::addNamespace(
                namespace: self::nameLower(),
                classNamespace: $namespace,
                classPath: $classDirectory,
                classViewPath: $viewDirectory,
            );
        }

        $viewDirectory = Module::pathFor(self::name(), 'components-view') . DIRECTORY_SEPARATOR . 'livewire';

        if (is_dir($viewDirectory)) {
            Livewire::addLocation(
                viewPath: $viewDirectory,
            );

            Livewire::addNamespace(
                namespace: self::nameLower(),
                viewPath: $viewDirectory,
            );
        }
    }

    /**
     * Boot and register all module components within the application lifecycle.
     */
    protected function registerAll(): void
    {
        $this->registerConfig();
        $this->registerCommands(); // Register commands and schedules

        $this->booting(function (): void {
            $this->registerDatabase();
            $this->registerViews();
            $this->registerTranslations();
            $this->registerComponents();
            $this->registerLivewireComponents();
        });

        $this->booted(function (): void {
            $this->registerCommandSchedules();
        });
    }
}
