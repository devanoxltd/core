<?php

declare(strict_types=1);

use Devanox\Core\Traits\Models\TenantConnection;
use Illuminate\Support\Facades\Config;

it('returns tenant connection from config', function (): void {
    Config::set('tenancy.database.tenant_connection', 'tenant_sqlite');

    $model = new class
    {
        use TenantConnection;
    };

    expect($model->getConnectionName())->toBe('tenant_sqlite');
});

it('returns default connection if tenant connection is not set', function (): void {
    $oldDefault = Config::get('database.default');
    Config::set('tenancy.database.tenant_connection');
    Config::set('database.default', 'mysql');
    Config::set('database.connections.mysql.driver', 'mysql');

    $model = new class
    {
        use TenantConnection;
    };

    expect($model->getConnectionName())->toBe('mysql');
    Config::set('database.default', $oldDefault);
});
