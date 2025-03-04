<?php

namespace Devanox\Core\Trait\Modules;

use Devanox\Core\Support\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

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
            ->map(fn (SplFileInfo $file) => str($namespace)
                ->append("\\{$file->getRelativePathname()}")
                ->replace(['/', '.php'], ['\\', ''])
                ->toString()
            )
            ->filter(fn ($class) => (is_subclass_of($class, Component::class) && ! (new ReflectionClass($class))->isAbstract()))
            ->each(fn ($class) => $this->registerSingleComponent($class, $namespace, $aliasPrefix));
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
        $directory = Module::pathFor(self::name(), 'livewire');

        if (file_exists($directory)) {
            $namespace = 'Modules\\' . self::name() . '\\App\\Livewire';
            $aliasPrefix = self::nameLower() . '::';

            $this->registerComponentDirectory($directory, $namespace, $aliasPrefix);
        }
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
            ->map(fn ($value) => str($value)->kebab())
            ->implode('.');

        str($class)->endsWith(['\Index', '\index'])
            ? Livewire::component(str($alias)->beforeLast('.index'), $class)
            : Livewire::component($alias, $class);
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
            if ($this->app->runningInConsole()) {
                $this->registerCommands();
            }

            $this->registerLivewireComponents();
        });

        $this->booted(function () {
            $this->registerCommandSchedules();
        });
    }
}
