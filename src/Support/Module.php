<?php

namespace Devanox\Core\Support;

use Devanox\Core\Events\ModuleDisabled;
use Devanox\Core\Events\ModuleEnabled;
use Illuminate\Support\Collection;

class Module
{
    const MODULES_PATH = 'modules';

    const ENABLE_FILE = 'enable';

    public static function path(string $module, ?bool $fullPath = false, ?bool $enable = false): string
    {
        $path = self::MODULES_PATH . DIRECTORY_SEPARATOR . $module;

        if ($enable) {
            $path .= DIRECTORY_SEPARATOR . self::ENABLE_FILE;
        }

        if ($fullPath) {
            return base_path($path);
        }

        return $path;
    }

    /**
     * @return array <string>
     */
    public static function providers(): array
    {
        $providers = [];

        $modules = self::getModules();

        foreach ($modules as $module) {
            $providerPath = self::path($module, true);
            $providerPath .= DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Providers';
            $providerFiles = glob($providerPath . DIRECTORY_SEPARATOR . '*ServiceProvider.php');

            foreach ($providerFiles as $providerFile) {
                $provider = basename($providerFile, '.php');
                $providers[] = self::namespace($module) . "\\App\\Providers\\{$provider}";
            }
        }

        return $providers;
    }

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        $modules = glob(base_path(self::MODULES_PATH . DIRECTORY_SEPARATOR . '*'), GLOB_ONLYDIR);

        $modules = array_map(function (string $module) {
            return basename($module);
        }, $modules);

        return $modules;
    }

    public static function config(string $module, ?string $path = null): array
    {
        if (! $path) {
            $path = self::path($module, true);
        } else {
            $path = $path . DIRECTORY_SEPARATOR . $module;
        }

        if (! file_exists($path)) {
            return [];
        }

        $configFile = $path . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';

        if (! file_exists($configFile)) {
            return [];
        }

        return include $configFile;
    }

    public static function isValid(string $module, ?string $path = null): bool
    {
        $config = self::config($module, $path);

        if (empty($config)) {
            return false;
        }

        return isset($config['id']);
    }

    /**
     * @return array<string>
     */
    public static function getModules(?bool $disable = false): array
    {
        $modules = self::all();

        $modules = array_filter($modules, function (string $module) use ($disable) {
            $file = file_exists(self::path($module, true, true));

            return $disable ? ! $file : $file;
        });

        return $modules;
    }

    public static function exist(string $module): bool
    {
        return file_exists(self::path($module, true));
    }

    public static function isEnabled(string $module): bool
    {
        return file_exists(self::path($module, true, true));
    }

    public static function isDisabled(string $module): bool
    {
        return ! file_exists(self::path($module, true, true));
    }

    public static function enable(string $module): void
    {
        $path = self::path($module, true, true);

        if (! file_exists($path)) {
            touch($path);
        }

        event(new ModuleEnabled($module));
    }

    public static function disable(string $module): void
    {
        $path = self::path($module, true, true);

        if (file_exists($path)) {
            unlink($path);
        }

        event(new ModuleDisabled($module));
    }

    /**
     * @return array <string>
     */
    public static function seeders(): array
    {
        $seeders = [];

        $modules = self::getModules();

        foreach ($modules as $module) {
            $seederPath = self::path($module, true);
            $seederPath .= DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Seeders';
            $seederFile = $seederPath . DIRECTORY_SEPARATOR . 'DatabaseSeeder.php';

            if (file_exists($seederFile)) {
                $seeders[] = self::namespace($module) . '\\Database\\Seeders\\DatabaseSeeder';
            }
        }

        return $seeders;
    }

    public static function prefix(string $module): string
    {
        return str($module)->kebab()->__toString();
    }

    public static function namespace(string $module): string
    {
        return "Modules\\{$module}";
    }

    public static function pathFor(string $module, string $for): string
    {
        $path = self::path($module, true);

        $forPath = self::forPath($for);

        if ($forPath === '') {
            return $path;
        }

        return $path . DIRECTORY_SEPARATOR . $forPath;
    }

    public static function forPath(string $for): string
    {
        return match ($for) {
            'app' => 'App',
            'livewire' => self::forPath('app') . DIRECTORY_SEPARATOR . 'Livewire',
            'components' => self::forPath('app') . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components',
            'components-view' => self::forPath('views') . DIRECTORY_SEPARATOR . 'components',
            'config' => 'Config',
            'database' => 'Database',
            'migrations' => self::forPath('database') . DIRECTORY_SEPARATOR . (function_exists('tenant') && tenant() ? 'Migrations' . DIRECTORY_SEPARATOR . 'tenant' : 'Migrations'),
            'factories' => self::forPath('database') . DIRECTORY_SEPARATOR . 'Factories',
            'seeders' => self::forPath('database') . DIRECTORY_SEPARATOR . 'Seeders',
            'lang' => 'Lang',
            'resources' => 'Resources',
            'routes' => 'Routes',
            'views' => self::forPath('resources') . DIRECTORY_SEPARATOR . 'views',
            default => '',
        };
    }

    public static function get(): Collection
    {
        $modules = self::all();

        return collect($modules)->map(function ($module) {
            return (object) [
                'name' => $module,
                'prefix' => self::prefix($module),
                'enabled' => self::isEnabled($module),
                'path' => self::path($module, true),
                'namespace' => self::namespace($module),
            ];
        });
    }
}
