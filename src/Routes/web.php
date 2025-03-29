<?php

use Devanox\Core\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;

Route::get('avatar/{provider}/{username?}', [AvatarController::class, 'index'])
    ->name('avatar');
if (!isAppInstalled()) {
    Route::view('install', 'core::install')->name('install');
    Route::view('licence', 'core::licence')->name('licence');
}
