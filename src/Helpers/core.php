<?php

use Devanox\Core\Models\License;
use Devanox\Core\Support\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

if (! function_exists('modules')) {
    function modules(?bool $disable = false): Collection
    {
        return Module::get()->where('enabled', $disable ? false : true);
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
        return Cache::remember('license.valid', 43200, function () {
            return License::isValidLicense();
        });
    }
}
