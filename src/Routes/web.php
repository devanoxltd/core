<?php

use Devanox\Core\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;

Route::get('avatar/{provider}/{username?}', [AvatarController::class, 'index'])
    ->name('devanox.avatar');
