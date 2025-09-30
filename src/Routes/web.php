<?php

use Illuminate\Support\Facades\Route;

if (!isAppInstalled()) {
    Route::view('install', 'core::install')->name('install');
}

Route::view('license', 'core::license')->name('license');
