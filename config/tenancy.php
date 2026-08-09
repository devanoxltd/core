<?php

declare(strict_types=1);

use Devanox\Core\Models\Domain;
use Devanox\Core\Models\Tenant;

return [
    /**
     * Enable or disable the tenancy system.
     *
     * When disabled, the package behaves as a single-tenant application.
     * Set to true in your published config to opt in to multi-tenancy.
     */
    'enabled' => false,
    /**
     * The list of domains hosting your central app.
     *
     * Only relevant if you're using the domain or subdomain identification middleware.
     * Example: ['example.com', 'www.example.com']
     */
    'central_domains' => [
        env('APP_DOMAIN', 'localhost'),
        '127.0.0.1',
        'localhost',
    ],
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'tenant_connection' => env('DB_TENANT_CONNECTION', 'mysql_tenant'),
        /**
         * Tenant database names are created like this:
         * prefix + tenant_id + suffix.
         */
        'prefix' => 'tenant_',
        'suffix' => '',
    ],
    /**
     * The current tenant instance.
     *
     * This is set by the tenancy middleware and can be used to access the current tenant.
     */
    'current' => null,
    /**
     * Package Models
     *
     * If the host application needs to add custom relationships or
     * logic to the Tenant or Domain models, they can create their own
     * models extending the package's base models and update the classes here.
     */
    'models' => [
        'tenant' => Tenant::class,
        'domain' => Domain::class,
    ],
];
