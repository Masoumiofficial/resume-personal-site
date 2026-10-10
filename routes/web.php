<?php

use Illuminate\Support\Facades\Route;

/*
| Public site routes are built in Phase 4. Until then the home page is a
| minimal, noindex placeholder so a deployment can be smoke-tested.
*/
Route::view('/', 'public.placeholder')->name('home');
