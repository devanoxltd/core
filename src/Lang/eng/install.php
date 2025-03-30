<?php

return [
    'title' => 'Devanox Installer',
    'notInstalled' => 'Application is not installed yet.',
    'notActivated' => 'Application is not activated yet.',
    'error' => [
        'title' => 'Error',
        'message' => 'Something went wrong. Please try again.',
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
            ],
            'max_execution_time' => 'Max Execution Time',
        ],
        'permissions' => [
            'title' => 'Folder Permissions',
            'description' => 'Please make sure the following folders have the correct permissions.',
            'button' => 'Next Step',
            'table' => [
                'name' => 'Folder',
                'permission' => 'Permission',
            ],
        ],
        'database' => [
            'title' => 'Database Configuration',
            'description' => 'Please provide your database connection details.',
            'button' => 'Configure Database',
            'form' => [
                'appUrl' => 'Application URL',
                'host' => 'Database Host',
                'port' => 'Database Port',
                'database' => 'Database Name',
                'dbUsername' => 'Database Username',
                'dbPassword' => 'Database Password',
                'submit' => 'Set Database Connection',
                'edit' => 'Edit Database Connection',
            ],
            'connection' => [
                'title' => 'Database Connection',
                'success' => 'Database connection credentials are valid. Now you are ready to setup your database.',
                'error' => 'Database connection failed. Please check your credentials.',
                'errorMessage' => 'Error message: :message',
            ],
        ],
        'migrations' => [
            'title' => 'Migrations',
            'description' => 'We are migrating your database tables. This may take a few minutes.',
            'success' => 'Migrations ran successfully.',
            'error' => 'Migrations failed. Please check your database connection.',
            'button' => 'Setup Account',
            'not_run' => 'Migrations will run shortly. Please wait...',
            'running' => 'Migrations are running. Please wait...',
            'complete' => 'Migrations are complete. You can now setup your account.',
        ],
        'admin' => [
            'title' => 'Admin Account',
            'description' => 'Please provide your admin account details.',
            'button' => 'Complete Installation',
            'success' => 'Admin account created successfully. Use this :email to log in to your application.',
            'form' => [
                'username' => 'Name',
                'email' => 'Email',
                'password' => 'Password',
                'passwordConfirmation' => 'Confirm Password',
                'submit' => 'Create Admin Account',
            ],
        ],
        'activation' => [
            'title' => 'Activation',
            'description' => 'Please provide your activation key.',
            'form' => [
                'licenseKey' => 'License Key',
                'getLicenseKey' => 'Get License Key',
                'submit' => 'Activate Application',
            ],
            'success' => 'Application activated successfully.',
            'error' => 'Something went wrong. Please try again. If the problem persists, please contact support.',
        ],
        'finish' => [
            'title' => 'Finish',
            'description' => 'Installation is complete. You can now log in to your application.',
            'button' => 'Go to Application',
        ],
    ],
];
