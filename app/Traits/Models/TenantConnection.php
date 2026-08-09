<?php

declare(strict_types=1);

namespace Devanox\Core\Traits\Models;

trait TenantConnection
{
    public function getConnectionName(): string
    {
        $tenantConnection = config('tenancy.database.tenant_connection');

        if (is_string($tenantConnection) && $tenantConnection !== '') {
            return $tenantConnection;
        }

        $default = config('database.default');

        return is_string($default) && $default !== '' ? $default : 'mysql';
    }
}
