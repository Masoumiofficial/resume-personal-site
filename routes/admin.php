<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SecurityController;
use Illuminate\Support\Facades\Route;

/*
| Admin panel. Loaded in bootstrap/app.php with:
|   prefix  = config('admin.path')   middleware = web, auth   name = admin.*
*/
Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/security', SecurityController::class)->name('security');
