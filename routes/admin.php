<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SecurityController;
use Illuminate\Support\Facades\Route;

/*
| Admin panel. Loaded in bootstrap/app.php with:
|   prefix  = config('admin.path')   middleware = web, auth   name = admin.*
*/
Route::get('/', DashboardController::class)->name('dashboard');
// "Sudo mode": the page asks for the password again (valid 3h, auth.password_timeout),
// so the 2FA buttons on it work directly instead of bouncing through a
// confirmation that would GET a POST-only endpoint.
Route::get('/security', SecurityController::class)->middleware('password.confirm')->name('security');
