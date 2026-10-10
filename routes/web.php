<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

/*
| Public site pages are built in Phase 4. Until then the home page is a
| minimal, noindex placeholder so a deployment can be smoke-tested.
*/
Route::view('/', 'public.placeholder')->name('home');

// Contact form submission (the form page itself arrives in Phase 4).
Route::post('/contact/', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
