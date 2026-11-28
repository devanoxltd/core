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
        'context_unavailable' => 'Tenant context is unavailable.',
        'invalid_model_configuration' => 'Invalid tenant model configuration.',
        'context_initialization_failed' => 'Tenant database context could not be initialized.',
        'context_mismatch' => 'Database context does not match the active tenant.',
        'invalid_identifier' => 'Invalid tenant identifier.',
    ],
    'commands' => [
        'error' => [
            'invalid_id' => 'Invalid tenant ID provided.',
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
        'queue' => [
            'tenancy_required' => 'Tenancy must be enabled.',
            'pcntl_required' => 'The pcntl extension is required for graceful shutdown and job timeouts.',
            'positive_option' => 'The --:option option must be a positive integer.',
            'non_negative_option' => 'The --:option option must be a non-negative integer.',
            'invalid_model' => 'Invalid tenant model configuration.',
            'invalid_tenant_identifier' => 'A tenant has an invalid identifier.',
            'worker_output' => 'Worker for tenant :tenant: :output',
            'worker_start_failed' => 'Unable to start the worker for tenant :tenant.',
            'worker_exited' => 'Worker for tenant :tenant exited with code :code.',
            'tenant_id_required' => 'A tenant ID is required.',
            'active_tenant_not_found' => 'Active tenant not found.',
            'database_configuration_missing' => 'Tenant database configuration is missing.',
            'invalid_tenant_id' => 'Invalid tenant ID.',
            'invalid_configuration' => 'Invalid tenant database, queue, batch, failed-job, or timeout configuration.',
            'database_not_isolated' => 'Tenant database connection does not identify a separate tenant database.',
            'table_missing' => 'Tenant queue, batch, failed-job, or cache table is missing.',
            'database_queue_required' => 'Tenant queue is not a database queue.',
            'worker_failed' => 'Unable to start or run the tenant queue worker.',
        ],
    ],
];
