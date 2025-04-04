<?php

use Devanox\Core\Models\Licence;
use Devanox\Core\Support\Module;
use Illuminate\Support\Facades\Cache;

if (! function_exists('modulePath')) {
    function modulePath(string $module, ?bool $fullPath = false, ?bool $enable = false): string
    {
        return Module::path($module, $fullPath, $enable);
    }
}

if (! function_exists('module')) {
    /**
     * @return array<string>
     */
    function module(): array
    {
        return Module::all();
    }
}

if (! function_exists('modules')) {
    /**
     * @return array<string>
     */
    function modules(?bool $disable = false): array
    {
        return Module::getModules($disable);
    }
}

if (! function_exists('moduleExist')) {
    function moduleExist(string $module): bool
    {
        return Module::exist($module);
    }
}

if (! function_exists('moduleIsEnabled')) {
    function moduleIsEnabled(string $module): bool
    {
        return Module::isEnabled($module);
    }
}

if (! function_exists('moduleIsDisabled')) {
    function moduleIsDisabled(string $module): bool
    {
        return Module::isDisabled($module);
    }
}

if (! function_exists('moduleEnable')) {
    function moduleEnable(string $module): void
    {
        Module::enable($module);
    }
}

if (! function_exists('moduleDisable')) {
    function moduleDisable(string $module): void
    {
        Module::disable($module);
    }
}

if (! function_exists('modulePrefix')) {
    function modulePrefix(string $module): string
    {
        return Module::prefix($module);
    }
}

if (! function_exists('isAppInstalled')) {
    function isAppInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }
}

if (! function_exists('isLicenceValid')) {
    function isLicenceValid(): bool
    {
        return Cache::remember('licence.valid', 60, function () {
            return Licence::isValidLicence();
        });
    }
}
