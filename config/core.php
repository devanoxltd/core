<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | PHP Version Requirements
    |--------------------------------------------------------------------------
    |
    | This is the required PHP version for the application. The system will
    | check if this version is met by comparing it with the current PHP version.
    |
    */
    'php' => '8.5.0',
    /*
    |--------------------------------------------------------------------------
    | Folder Permissions
    |--------------------------------------------------------------------------
    |
    | These are the default required folder permissions. If your application
    | requires more permissions, just add them to the array list below.
    |
    */
    'permissions' => [
        'storage/app/' => '700',
        'storage/framework/' => '700',
        'storage/logs/' => '700',
        'bootstrap/cache/' => '700',
    ],
    /*
    |--------------------------------------------------------------------------
    | Required Extensions
    |--------------------------------------------------------------------------
    |
    | These are the PHP extensions that are required to run the application. You
    | can add more extensions to this list if your application requires them.
    |
    */
    'extensions' => [
        'bcmath',
        'ctype',
        'curl',
        'date',
        'dom',
        'exif',
        'fileinfo',
        'filter',
        'gd',
        'hash',
        'iconv',
        'intl',
        'json',
        'libxml',
        'mbstring',
        'openssl',
        'pcntl',
        'pcre',
        'pdo',
        'phar',
        'reflection',
        'session',
        'simplexml',
        'sodium',
        'tokenizer',
        'xml',
        'xmlreader',
        'xmlwriter',
        'zip',
        'zlib',
    ],
    /*
    |--------------------------------------------------------------------------
    | Required Functions
    |--------------------------------------------------------------------------
    |
    | These are the required PHP functions. You can add as many as your
    | application requires.
    |
    */

    'functions' => [
        'escapeshellarg',
        'escapeshellcmd',
        'exec',
        'getenv',
        'passthru',
        'pcntl_alarm',
        'pcntl_async_signals',
        'pcntl_fork',
        'pcntl_signal',
        'pcntl_signal_dispatch',
        'pcntl_wait',
        'pcntl_waitpid',
        'pclose',
        'popen',
        'proc_close',
        'proc_get_status',
        'proc_open',
        'proc_terminate',
        'putenv',
        'shell_exec',
        'symlink',
        'system',
    ],
    /*
    |--------------------------------------------------------------------------
    | Required PHP Settings
    |--------------------------------------------------------------------------
    |
    | These are the required PHP settings. You can add as many as your
    | application requires.
    |
    */
    'max_execution_time' => 30,
    /*
    |--------------------------------------------------------------------------
    | Allowed Routes While Installing
    |--------------------------------------------------------------------------
    |
    | These are the routes that are allowed to be accessed while the application
    | is installing. You can add more routes as your application requires.
    |
    */
    'route_allow' => [
        'install',
        'livewire-*/*',
        'dev/*',
        'file/*',
    ],
    /*
    |--------------------------------------------------------------------------
    | License Activation URL
    |--------------------------------------------------------------------------
    |
    | This is the URL used for application updates and license activation.
    |
    */

    'url' => [
        'server' => 'https://devanox-activate.test', // TODO: update this URL to your production URL https://devanox.com
    ],
    /*
    |--------------------------------------------------------------------------
    | Module Path
    |--------------------------------------------------------------------------
    |
    | This is the path where the application's modules are located relative
    | to the base path.
    |
    */
    'module_path' => 'modules',
];
