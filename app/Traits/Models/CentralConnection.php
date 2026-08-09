<?php

declare(strict_types=1);

namespace Devanox\Core\Traits\Models;

trait CentralConnection
{
    public function getConnectionName(): string
    {
        if (config('tenancy.enabled', false) === false) {
            $default = config('database.default');

            return is_string($default) && $default !== '' ? $default : 'mysql';
        }

        $central = config('tenancy.database.central_connection');

        if (is_string($central) && $central !== '') {
            return $central;
        }

        $default = config('database.default');

        return is_string($default) && $default !== '' ? $default : 'mysql';
    }
}
