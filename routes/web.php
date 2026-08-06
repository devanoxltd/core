<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use function Devanox\Core\Helpers\isAppInstalled;

if (! isAppInstalled()) {
    Route::view('install', 'core::install')->name('install');
}

Route::view('license', 'core::license')->name('license');
