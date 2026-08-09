<?php

declare(strict_types=1);

return [
    'enum' => [
        'domain' => [
            'status' => [
                'label' => [
                    'pending' => 'Pending',
                    'verified' => 'Verified',
                    'approval' => 'Awaiting Approval',
                    'rejected' => 'Rejected',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
            ],
            'type' => [
                'label' => [
                    'domain' => 'Domain',
                    'subdomain' => 'Subdomain',
                ],
                'description' => [
                    'domain' => 'A custom domain',
                    'subdomain' => 'An application subdomain',
                ],
            ],
        ],
        'tenant' => [
            'status' => [
                'label' => [
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                ],
            ],
        ],
    ],
    'exceptions' => [
        'database_configurations_not_found' => 'Tenant `:name` database configurations not found',
    ],
    'commands' => [
        'error' => [
            'tenant_not_found' => 'Tenant `:id` not found',
        ],
        'database' => [
            'created' => 'Database `:database` has been created successfully',
            'user_created' => 'Database user `:username` has been created successfully',
            'permissions_granted' => 'Permissions granted to database `:database`',
            'privileges_flushed' => 'Database privileges flushed successfully',
            'completed' => 'Database setup completed for `:database`',
            'configuration_saved' => 'Tenant database configuration saved successfully',
        ],
    ],
];
