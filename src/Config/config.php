<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Requirements
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel server requirements, you can add as many
    | as your application require, we check if the extension is enabled
    | by looping through the array and run "extension_loaded" on it.
    |
    */
    'phpVersion' => '8.4.0',

    'requirements' => [
        'php' => [
            'iconv',
            'json',
            'ctype',
            'filter',
            'hash',
            'mbstring',
            'openssl',
            'session',
            'tokenizer',
            'fileinfo',
            'zlib',
            'dom',
            'libxml',
            'pcre',
            'reflection',
            'simplexml',
            'xml',
            'phar',
            'xmlwriter',
            'curl',
            'pdo',
            'sodium',
            'zip',
            'intl',
            'gd',
        ],
        'php_function' => [
            'proc_open',
            'proc_close',
        ],
        'max_execution_time' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Folders Permissions
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel folders permissions, if your application
    | requires more permissions just add them to the array list bellow.
    |
    */
    'permissions' => [
        'storage/app/'           => '755',
        'storage/framework/'     => '755',
        'storage/logs/'          => '755',
        'bootstrap/cache/'       => '755',
    ],

    'installRoutes' => [
        'install',
        'livewire/*',
        'dev/*',
        'file/*',
    ],

    'url' => [
        'server' => 'https://devanox-activate.test', // TODO : update this URL to your production URL https://devanox.com
    ],
];
