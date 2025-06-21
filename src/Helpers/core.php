<?php

use Devanox\Core\Models\License;
use Devanox\Core\Support\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

if (! function_exists('modulePath')) {
    function modulePath(string $module, ?bool $fullPath = false, ?bool $enable = false): string
    {
        return Module::path($module, $fullPath, $enable);
    }
}

if (! function_exists('module')) {
    function module(): Collection
    {
        return Module::get();
    }
}

if (! function_exists('modules')) {
    function modules(?bool $disable = false): Collection
    {
        return Module::get()->where('enabled', $disable ? false : true);
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

if (! function_exists('isLicenseValid')) {
    function isLicenseValid(): bool
    {
        return Cache::remember('license.valid', 60, function () {
            return License::isValidLicense();
        });
    }
}
