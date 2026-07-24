<?php

declare(strict_types=1);

return [
    'error' => [
        'app_install_missing' => 'The app:install command is not registered or available.',
        'migration_failed' => 'Installation command failed with exit code :code.',
    ],
    'title' => 'Devanox Installer',
    'description' => 'Welcome to the Devanox Installer. Please follow the steps below to install the application.',
    'not_installed' => 'Application is not installed yet.',
    'not_activated' => 'Application is not activated yet.',
    'license' => [
        'title' => 'License Activation',
        'description' => 'Please enter your license key to activate the application.',
    ],
    'steps' => [
        'home' => [
            'title' => 'Welcome to Devanox Installer',
            'description' => 'This installer will guide you through the installation process of application.',
            'button' => 'Start Installation',
        ],
        'requirements' => [
            'title' => 'Server Requirements',
            'description' => 'Please make sure your server meets the following requirements.',
            'button' => 'Next Step',
            'table' => [
                'name' => 'Requirement',
                'status' => 'Status',
                'refresh_requirements' => 'Refresh :name requirement',
            ],
            'max_execution_time' => 'Max Execution Time',
        ],
        'permissions' => [
            'title' => 'Folder Permissions',
            'description' => 'Please make sure the following folders have the correct permissions.',
            'button' => 'Next Step',
            'table' => [
                'name' => 'Folder',
                'current' => 'Current',
                'required' => 'Required',
                'status' => 'Status',
                'fix_permissions' => 'Fix Permissions for :folder',
                'refresh_permissions' => 'Refresh Permissions for :folder',
            ],
        ],
        'database' => [
            'title' => 'Database Configuration',
            'description' => 'Please provide your database connection details.',
            'button' => 'Configure Database',
            'form' => [
                'app_url' => 'Application URL',
                'host' => 'Database Host',
                'port' => 'Database Port',
                'database' => 'Database Name',
                'db_username' => 'Database Username',
                'db_password' => 'Database Password',
                'submit' => 'Set Database Connection',
                'edit' => 'Edit Database Connection',
            ],
            'connection' => [
                'title' => 'Database Connection',
                'success' => 'Database connection credentials are valid. Now you are ready to setup your database.',
                'error' => 'Database connection failed. Please check your credentials.',
                'error_message' => 'Error message: :message',
            ],
        ],
        'migrations' => [
            'title' => 'Migrations',
            'description' => 'We are migrating your database tables. This may take a few minutes.',
            'button' => 'Setup Account',
            'not_run' => 'Migrations will run shortly. Please wait...',
            'running' => 'Migrations are running. Please wait...',
            'complete' => 'Migrations are complete. You can now setup your account.',
        ],
        'admin' => [
            'title' => 'Admin Account',
            'description' => 'Please create an admin account for the application.',
            'button' => 'Complete Installation',
            'success' => 'Admin account created successfully. Use this :email to log in to your application.',
            'form' => [
                'username' => 'Name',
                'email' => 'Email',
                'password' => 'Password',
                'password_confirmation' => 'Confirm Password',
                'submit' => 'Create Admin Account',
            ],
        ],
        'finish' => [
            'title' => 'Installation Complete',
            'description' => 'The application has been successfully installed. You can now log in with your admin account.',
            'button' => 'Go to Application',
        ],
        'activation' => [
            'title' => 'Activation',
            'description' => 'Please provide your activation key.',
            'form' => [
                'license_key' => 'License Key',
                'get_license_key' => 'Get License Key',
                'submit' => 'Activate Application',
            ],
            'success' => 'Application activated successfully.',
        ],
    ],
];
