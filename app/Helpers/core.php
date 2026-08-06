<?php

declare(strict_types=1);

namespace Devanox\Core\Helpers;

use Devanox\Core\Models\License;
use Devanox\Core\Support\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use stdClass;

if (! function_exists('Devanox\Core\Helpers\isAppInstalled')) {
    /**
     * Check if the application has been installed.
     */
    function isAppInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }
}

if (! function_exists('Devanox\Core\Helpers\modules')) {
    /**
     * Get a collection of modules.
     *
     * @param  bool|null  $status  true for enabled, false for disabled, null for all
     * @return Collection<int, stdClass&object{id: ?string, name: string, prefix: string, enabled: bool, path: string, namespace: string, config: object{requiredModules?: array<int, string>}, is_valid: bool}>
     */
    function modules(?bool $status = true): Collection
    {
        $modules = Module::get();

        if (is_null($status)) {
            return $modules;
        }

        return $modules->where('enabled', $status);
    }
}

if (! function_exists('Devanox\Core\Helpers\isLicenseValid')) {
    /**
     * Check if a valid license exists for the core application or a specific module.
     *
     * @param  string|null  $module  The module name, or null for core
     */
    function isLicenseValid(?string $module = null): bool
    {
        $cacheKey = $module ? 'license.valid.' . $module : 'license.valid.core';

        return Cache::remember($cacheKey, now()->addMinutes(30), fn (): bool => License::isValidLicense($module));
    }
}
