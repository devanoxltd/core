<?php

declare(strict_types=1);

use Devanox\Core\Traits\Models\CentralConnection;
use Illuminate\Support\Facades\Config;

it('returns mysql default when tenancy is disabled and no default', function (): void {
    $oldDefault = Config::get('database.default');
    Config::set('tenancy.enabled', false);
    Config::set('database.default');
    Config::set('database.connections.mysql.driver', 'mysql');

    $model = new class
    {
        use CentralConnection;
    };

    expect($model->getConnectionName())->toBe('mysql');
    Config::set('database.default', $oldDefault);
});

it('returns default connection when tenancy is disabled', function (): void {
    Config::set('tenancy.enabled', false);
    Config::set('database.default', 'sqlite');

    $model = new class
    {
        use CentralConnection;
    };

    expect($model->getConnectionName())->toBe('sqlite');
});

it('returns central connection when tenancy is enabled', function (): void {
    Config::set('tenancy.enabled', true);
    Config::set('tenancy.database.central_connection', 'central_sqlite');

    $model = new class
    {
        use CentralConnection;
    };

    expect($model->getConnectionName())->toBe('central_sqlite');
});

it('returns mysql when central connection is not string and tenancy enabled', function (): void {
    $oldDefault = Config::get('database.default');
    Config::set('tenancy.enabled', true);
    Config::set('tenancy.database.central_connection', ['array']);
    Config::set('database.default');
    Config::set('database.connections.mysql.driver', 'mysql');

    $model = new class
    {
        use CentralConnection;
    };

    expect($model->getConnectionName())->toBe('mysql');
    Config::set('database.default', $oldDefault);
});
