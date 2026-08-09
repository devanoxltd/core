<?php

declare(strict_types=1);

namespace Devanox\Core\Helpers;

use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Devanox\Core\Support\Tenancy;

if (! function_exists('Devanox\Core\Helpers\tenancy')) {
    function tenancy(): Tenancy
    {
        return resolve(Tenancy::class);
    }
}

if (! function_exists('Devanox\Core\Helpers\tenant')) {
    function tenant(): ?TenantContract
    {
        if (config('tenancy.enabled', false) === false) {
            return null;
        }

        return tenancy()->tenant;
    }
}
