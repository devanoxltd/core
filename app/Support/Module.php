<?php

declare(strict_types=1);

namespace Devanox\Core\Support;

use Devanox\Core\Events\ModuleDisabled;
use Devanox\Core\Events\ModuleEnabled;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use stdClass;

final class Module
{
    public const string MODULES_PATH = 'modules';

    public const string ENABLE_FILE = 'enable';

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $configCache = [];

    /**
     * @var Collection<int, stdClass&object{id: ?string, name: string, prefix: string, enabled: bool, path: string, namespace: string, config: object{requiredModules?: array<int, string>}, is_valid: bool}>|null
     */
    private static ?Collection $modulesCache = null;

    public static function clearCache(): void
    {
        self::$configCache = [];
        self::$modulesCache = null;
    }

    public static function path(string $module, bool $fullPath = false, bool $enable = false): string
    {
        $base = Config::string('core.module_path', self::MODULES_PATH);
        $path = $base . DIRECTORY_SEPARATOR . $module;

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

        $modules = self::get()->where('enabled', true);

        foreach ($modules as $module) {
            $providerPath = self::pathFor($module->name, 'app') . DIRECTORY_SEPARATOR . 'Providers';
            $providerFiles = glob($providerPath . DIRECTORY_SEPARATOR . '*ServiceProvider.php') ?: [];

            foreach ($providerFiles as $providerFile) {
                $provider = basename($providerFile, '.php');
                $providers[] = self::namespace($module->name) . ('\\App\\Providers\\' . $provider);
            }
        }

        return $providers;
    }

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        $modules = glob(base_path(Config::string('core.module_path', self::MODULES_PATH) . DIRECTORY_SEPARATOR . '*'), GLOB_ONLYDIR) ?: [];

        return array_map(basename(...), $modules);
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(string $module, ?string $path = null): array
    {
        $cacheKey = $module . '_' . ($path ?? 'default');

        if (isset(self::$configCache[$cacheKey])) {
            return self::$configCache[$cacheKey];
        }

        $path = $path ? $path . DIRECTORY_SEPARATOR . $module : self::path($module, true);

        if (! file_exists($path)) {
            return [];
        }

        $configFile = $path . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';

        if (! file_exists($configFile)) {
            return [];
        }

        $config = include $configFile;

        /** @var array<string, mixed> $configArray */
        $configArray = is_array($config) ? $config : [];
        self::$configCache[$cacheKey] = $configArray;

        return self::$configCache[$cacheKey];
    }

    public static function isValid(string $module, ?string $path = null): bool
    {
        $config = self::config($module, $path);

        return ! empty($config['id']);
    }

    public static function exist(string $module): bool
    {
        return is_dir(self::path($module, true));
    }

    public static function isEnabled(string $module): bool
    {
        return is_file(self::path($module, true, true));
    }

    public static function isDisabled(string $module): bool
    {
        return ! self::isEnabled($module);
    }

    public static function enable(string $module): void
    {
        if (! self::isRequirementsFullFill($module)) {
            throw new Exception(__('core::module.requirements_not_fulfilled', ['module' => $module]));
        }

        $path = self::path($module, true, true);

        if (! file_exists($path)) {
            touch($path);
            Artisan::call('migrate', [
                '--path' => str(self::pathFor($module, 'migrations'))->replace(base_path(DIRECTORY_SEPARATOR), '')->__toString(),
                '--force' => true,
            ]);
        }

        self::clearCache();

        event(new ModuleEnabled($module));
    }

    public static function disable(string $module): void
    {
        $path = self::path($module, true, true);

        if (is_file($path)) {
            unlink($path);
        }

        $moduleIds = self::get()->where('enabled', true)
            ->where('name', '!=', $module);

        $config = self::config($module);
        $id = $config['id'] ?? null;

        if ($id) {
            $moduleIds->each(function (object $module) use ($id): void {
                $requiredModules = $module->config->requiredModules ?? [];

                if (in_array($id, $requiredModules)) {
                    self::disable($module->name);
                }
            });
        }

        self::clearCache();

        event(new ModuleDisabled($module));
    }

    /**
     * @return array <string>
     */
    public static function seeders(): array
    {
        $seeders = [];

        $modules = self::get()->where('enabled', true);

        foreach ($modules as $module) {
            $seederFile = self::pathFor($module->name, 'seeders') . DIRECTORY_SEPARATOR . 'DatabaseSeeder.php';

            if (file_exists($seederFile)) {
                $seeders[] = self::namespace($module->name) . '\\Database\\Seeders\\DatabaseSeeder';
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
        return 'Modules\\' . $module;
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
            // TODO: Handle tenant migrations after implementing multi-tenancy
            // 'migrations' => self::forPath('database') . DIRECTORY_SEPARATOR . (tenant() ? 'Migrations' . DIRECTORY_SEPARATOR . 'tenant' : 'Migrations'),
            'migrations' => self::forPath('database') . DIRECTORY_SEPARATOR . 'Migrations',
            'factories' => self::forPath('database') . DIRECTORY_SEPARATOR . 'Factories',
            'seeders' => self::forPath('database') . DIRECTORY_SEPARATOR . 'Seeders',
            'lang' => 'Lang',
            'resources' => 'Resources',
            'routes' => 'Routes',
            'views' => self::forPath('resources') . DIRECTORY_SEPARATOR . 'views',
            default => '',
        };
    }

    /**
     * @return Collection<int, stdClass&object{id: ?string, name: string, prefix: string, enabled: bool, path: string, namespace: string, config: object{requiredModules?: array<int, string>}, is_valid: bool}>
     */
    public static function get(): Collection
    {
        if (! self::$modulesCache instanceof Collection) {
            /** @var Collection<int, stdClass&object{id: ?string, name: string, prefix: string, enabled: bool, path: string, namespace: string, config: object{requiredModules?: array<int, string>}, is_valid: bool}> $modules */
            $modules = collect(self::all())->map(fn (string $module): object => self::info($module));
            self::$modulesCache = $modules;
        }

        return self::$modulesCache;
    }

    public static function info(string $module): stdClass
    {
        $config = (object) self::config($module);

        return (object) [
            'id' => $config->id ?? null,
            'name' => $module,
            'prefix' => self::prefix($module),
            'enabled' => self::isEnabled($module),
            'path' => self::path($module, true),
            'namespace' => self::namespace($module),
            'config' => $config,
            'is_valid' => self::isValid($module),
        ];
    }

    public static function isRegisterForApp(string $module, ?string $path = null): ?object
    {
        $config = self::config($module, $path);

        if (empty($config['id'])) {
            return null;
        }

        $registeredServerUrl = config('core.url.server');

        if (! $registeredServerUrl) {
            return null;
        }

        /** @var string $registeredServerUrl */
        $registeredServerUrl .= '/api/module/is-registered-for-app';

        try {
            $response = Http::acceptJson()->post($registeredServerUrl, [
                'id' => $config['id'],
                'version' => $config['version'] ?? '0.0',
                'app_id' => config('app.id'),
                'app_version' => config('app.version'),
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
            ]);

            if ($response->failed()) {
                return null;
            }

            return $response->object();
        } catch (Exception) {
            return null;
        }
    }

    public static function isRequirementsFullFill(string $module, ?string $path = null): bool
    {
        /** @var array<int, string> $requiredModules */
        $requiredModules = self::config($module, $path)['requiredModules'] ?? [];

        if (empty($requiredModules)) {
            return true;
        }

        $modules = self::get()->where('enabled', true);

        /** @var array<int, string> $moduleIds */
        $moduleIds = $modules->pluck('id')->filter()->toArray();

        return array_diff($requiredModules, $moduleIds) === [];
    }
}
