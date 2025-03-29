<?php

use Devanox\Core\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;

if (!isAppInstalled()) {
    Route::view('install', 'core::install')->name('install');
}

Route::get('avatar/{provider}/{username?}', [AvatarController::class, 'index'])
    ->name('avatar');

Route::view('licence', 'core::licence')->name('licence');
