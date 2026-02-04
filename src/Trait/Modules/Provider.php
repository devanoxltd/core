<?php

namespace Devanox\Core\Trait\Modules;

use Devanox\Core\Support\Module;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use ReflectionClass;

/**
 * This trait is used to register all the necessary components of a module.
 * It is used in the service provider of the module.
 */
trait Provider
{
    public static function name(): string
    {
        $reflection = new ReflectionClass(get_called_class());

        return str($reflection->getName())->replace('Modules\\', '')->before('\\')->__toString();
    }

    public static function nameLower(): string
    {
        return Module::prefix(self::name());
    }

    public static function currentPath(): string
    {
        $reflection = new ReflectionClass(get_called_class());

        return dirname($reflection->getFileName());
    }

    private function registerDatabase(): void
    {
        $migrationPath = Module::pathFor(self::name(), 'migrations');

        if (file_exists($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    private function registerConfig(): void
    {
        $configPath = Module::pathFor(self::name(), 'config') . DIRECTORY_SEPARATOR . 'config.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, self::nameLower());
        }

        $configValues = [
            'name' => self::name(),
            'nameLower' => self::nameLower(),
            'path' => Module::path(self::name()),
        ];

        foreach ($configValues as $key => $value) {
            // @phpstan-ignore-next-line
            $this->app->config->set(self::nameLower() . '.' . $key, $value);
        }
    }

    private function registerViews(): void
    {
        $viewPath = Module::pathFor(self::name(), 'views');

        if (file_exists($viewPath)) {
            $this->loadViewsFrom($viewPath, self::nameLower());
        }
    }

    private function registerTranslations(): void
    {
        $translationPath = Module::pathFor(self::name(), 'lang');

        if (file_exists($translationPath)) {
            $this->loadTranslationsFrom($translationPath, self::nameLower());
            $this->loadJsonTranslationsFrom($translationPath);
        }
    }

    private function registerComponents(): void
    {
        $componentPath = Module::pathFor(self::name(), 'components');

        if (file_exists($componentPath)) {
            Blade::componentNamespace('Modules\\' . self::name() . '\\App\\View\\Components', self::nameLower());
        }

        $anonymousComponentPath = Module::pathFor(self::name(), 'components-view');

        if (file_exists($anonymousComponentPath)) {
            Blade::anonymousComponentPath($anonymousComponentPath, self::nameLower());
        }
    }

    private function registerCommands(): void
    {
        // @phpstan-ignore-next-line
        if (isset($this->commands)) {
            $this->commands($this->commands);
        }
    }

    private function registerCommandSchedules(): void
    {
        // declare this method in the service provider
        // following is an example of how to register a schedule
        // $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
        // $schedule->command('inspire')->everyMinute();
    }

    private function registerLivewireComponents(): void
    {
        $classDirectory = Module::pathFor(self::name(), 'livewire');
        $viewDirectory = Module::pathFor(self::name(), 'views') . DIRECTORY_SEPARATOR . 'livewire';

        if (file_exists($classDirectory)) {
            $namespace = 'Modules\\' . self::name() . '\\App\\Livewire';

            Livewire::addLocation(
                classNamespace: $namespace
            );

            Livewire::addNamespace(
                namespace: self::nameLower(),
                classNamespace: $namespace,
                classPath: $classDirectory,
                classViewPath: $viewDirectory
            );
        }

        Livewire::addLocation(
            viewPath: $viewDirectory
        );

        Livewire::addNamespace(
            namespace: self::nameLower(),
            viewPath: $viewDirectory
        );
    }

    private function registerAll(): void
    {
        $this->booting(function () {
            $this->registerConfig();
            $this->registerDatabase();
            $this->registerViews();
            $this->registerTranslations();
            $this->registerComponents();

            // Register commands and schedules
            $this->registerCommands();

            $this->registerLivewireComponents();
        });

        $this->booted(function () {
            $this->registerCommandSchedules();
        });
    }
}
